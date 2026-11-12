<?php

namespace Tests\Unit\AI;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Services\AI\Context\AIContextBuilder;
use App\Services\AI\DataTruthLevel;
use App\Services\Analysis\ProductAnalysisEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected AIContextBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AIContextBuilder(new ProductAnalysisEngine());
    }

    public function test_builds_structured_context_with_strict_data_truth_tags(): void
    {
        $category = Category::factory()->create(['name' => 'Printers & Accessories']);
        $source = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'HP OfficeJet Pro 9015e Wireless Printer',
            'slug' => 'hp-officejet-pro-9015e',
            'brand' => 'HP',
            'description' => 'Reliable all-in-one wireless printer',
            'base_currency' => 'USD',
        ]);

        ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'v1|EBAY-PRINTER-001|0',
            'title' => 'HP OfficeJet Pro 9015e Wireless Printer New in Box',
            'price' => 220.00,
            'original_price' => 280.00,
            'currency' => 'USD',
            'rating' => null, // Explicitly no catalog rating
            'reviews_count' => 0,
            'is_available' => true,
            'raw_payload' => [
                'seller' => [
                    'username' => 'official_hp_partner',
                    'feedbackPercentage' => 99.4,
                    'feedbackScore' => 8420,
                ],
                'seller_feedback_percentage' => 99.4,
                'seller_feedback_score' => 8420,
            ],
            'url' => 'https://www.ebay.com/itm/001',
        ]);

        $financialInputs = [
            'purchase_price' => 120.00,
            'selling_price' => 220.00,
            'shipping_cost' => 15.00,
            'estimated_fees' => 22.00,
            'other_costs' => 5.00,
            'currency' => 'USD',
        ];

        $context = $this->builder->build($canonical, $financialInputs);

        // 1. Verify Structure and Data Truth Tags
        $this->assertEquals('canonical_product', $context['subject_type']);
        $this->assertEquals('HP OfficeJet Pro 9015e Wireless Printer', $context['product']['name']['value']);
        $this->assertEquals(DataTruthLevel::OBSERVED, $context['product']['name']['truth']);
        $this->assertEquals('HP', $context['product']['brand']['value']);
        $this->assertEquals(DataTruthLevel::OBSERVED, $context['product']['brand']['truth']);

        // 2. Verify Seller vs Product Decoupling
        $offer = $context['market']['offers'][0];
        $this->assertEquals('official_hp_partner', $offer['seller_signals']['supplier_username']['value']);
        $this->assertEquals(99.4, $offer['seller_signals']['seller_feedback_percentage']['value']);
        $this->assertEquals(8420, $offer['seller_signals']['seller_feedback_score']['value']);
        $this->assertStringContainsString('Strictly seller reputation', $offer['seller_signals']['seller_feedback_percentage']['note']);
        
        // Product reviews must reflect DATA_UNAVAILABLE rather than copying seller score
        $this->assertEquals('DATA_UNAVAILABLE', $offer['product_reviews']['rating']['value']);
        $this->assertEquals('DATA_UNAVAILABLE', $offer['product_reviews']['reviews_count']['value']);

        // 3. Verify Deterministic Financial Totals (BCMath)
        // Total cost = 120 + 15 + 22 + 5 = 162.0000
        // Profit = 220 - 162 = 58.0000
        // Margin = (58 / 220) * 100 = 26.3636 (numeric, no '%')
        // ROI = (58 / 162) * 100 = 35.8024 (numeric, no '%')
        $fin = $context['financial_analysis'];
        $this->assertEquals('162.0000', $fin['total_cost']['value']);
        $this->assertEquals(DataTruthLevel::CALCULATED, $fin['total_cost']['truth']);
        $this->assertEquals('58.0000', $fin['estimated_profit']['value']);
        $this->assertEquals(DataTruthLevel::CALCULATED, $fin['estimated_profit']['truth']);
        $this->assertIsFloat($fin['profit_margin_percent']['value']);
        $this->assertSame(26.3636, $fin['profit_margin_percent']['value']);
        $this->assertIsFloat($fin['return_on_investment_percent']['value']);
        $this->assertSame(35.8024, $fin['return_on_investment_percent']['value']);
        $this->assertTrue($fin['is_profitable']['value']);

        // 4. Verify Heuristics
        $this->assertEquals(DataTruthLevel::HEURISTIC, $context['deterministic_heuristics']['opportunity_score']['truth']);
        $this->assertEquals(DataTruthLevel::HEURISTIC, $context['deterministic_heuristics']['risk_score']['truth']);
    }

    public function test_handles_missing_market_offers_gracefully_with_metadata_flag(): void
    {
        $canonicalWithoutListings = CanonicalProduct::create([
            'name' => 'Ghost Product Without Offers',
            'slug' => 'ghost-product',
            'base_currency' => 'USD',
        ]);

        $context = $this->builder->build($canonicalWithoutListings, [
            'purchase_price' => 50.00,
            'selling_price' => 80.00,
        ]);

        $this->assertEquals(0, $context['market']['offers_count']);
        $this->assertFalse($context['data_truth_meta']['has_active_listings']);
        $this->assertContains('active_marketplace_offers', $context['data_truth_meta']['missing_critical_data']);
    }

    public function test_missing_selling_price_on_canonical_product_does_not_fabricate_markup_fallback(): void
    {
        $category = Category::factory()->create(['name' => 'Smart Watches']);
        $source = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Garmin Fenix 7 Solar Smartwatch',
            'slug' => 'garmin-fenix-7-solar-no-selling',
            'brand' => 'Garmin',
            'base_currency' => 'USD',
        ]);

        ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'v1|GARMIN-7-NOSELL|0',
            'title' => 'Garmin Fenix 7 Solar Multisport GPS Watch',
            'price' => 599.99,
            'currency' => 'USD',
            'is_available' => true,
            'url' => 'https://www.ebay.com/itm/garmin7-nosell',
        ]);

        $context = $this->builder->build($canonical, []);
        $fin = $context['financial_analysis'];
        $rawJson = json_encode($context, JSON_THROW_ON_ERROR);

        // Purchase price remains observed from active listing; synthetic 1.45x (869.9855) must never appear
        $this->assertSame('599.9900', $fin['purchase_price']['value']);
        $this->assertSame(DataTruthLevel::OBSERVED, $fin['purchase_price']['truth']);
        $this->assertStringNotContainsString('869.9855', $rawJson);
        $this->assertStringNotContainsString('31.0344', $rawJson);

        // Missing selling price and derived profitability metrics must remain null and never tagged CALCULATED
        $this->assertNull($fin['selling_price']['value']);
        $this->assertNull($fin['selling_price']['truth']);
        $this->assertNotSame(DataTruthLevel::CALCULATED, $fin['selling_price']['truth']);
        $this->assertSame('DATA_UNAVAILABLE', $fin['selling_price']['status']);

        $this->assertNull($fin['estimated_profit']['value']);
        $this->assertNull($fin['estimated_profit']['truth']);
        $this->assertSame('INSUFFICIENT_DATA', $fin['estimated_profit']['status']);

        $this->assertNull($fin['profit_margin_percent']['value']);
        $this->assertNull($fin['profit_margin_percent']['truth']);

        $this->assertNull($fin['return_on_investment_percent']['value']);
        $this->assertNull($fin['return_on_investment_percent']['truth']);

        $this->assertNull($fin['is_profitable']['value']);
        $this->assertNotTrue($fin['is_profitable']['value']);
        $this->assertNull($fin['is_profitable']['truth']);

        $this->assertContains('verified_selling_price', $context['data_truth_meta']['missing_critical_data']);
    }

    public function test_missing_selling_price_on_listing_omits_synthetic_markup_while_explicit_selling_price_preserves_calculated_metrics(): void
    {
        $category = Category::factory()->create(['name' => 'Smart Watches']);
        $source = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Garmin Fenix 7 Pro',
            'slug' => 'garmin-fenix-7-pro',
            'brand' => 'Garmin',
            'base_currency' => 'USD',
        ]);

        $listing = ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'v1|GARMIN-7-PRO|0',
            'title' => 'Garmin Fenix 7 Pro',
            'price' => 599.99,
            'currency' => 'USD',
            'is_available' => true,
            'url' => 'https://www.ebay.com/itm/garmin7-pro',
        ]);

        // 1. Purchase price supplied, selling_price omitted -> must NOT compute 350 * 1.45 = 507.5000
        $missingSellingContext = $this->builder->build($listing, [
            'purchase_price' => 350.00,
        ]);
        $missingFin = $missingSellingContext['financial_analysis'];

        $this->assertSame('350.0000', $missingFin['purchase_price']['value']);
        $this->assertSame(DataTruthLevel::ESTIMATED, $missingFin['purchase_price']['truth']);
        $this->assertStringNotContainsString('507.5000', json_encode($missingSellingContext, JSON_THROW_ON_ERROR));
        $this->assertNull($missingFin['selling_price']['value']);
        $this->assertNull($missingFin['selling_price']['truth']);
        $this->assertSame('DATA_UNAVAILABLE', $missingFin['selling_price']['status']);
        $this->assertNull($missingFin['estimated_profit']['value']);
        $this->assertNull($missingFin['profit_margin_percent']['value']);
        $this->assertNull($missingFin['return_on_investment_percent']['value']);
        $this->assertNull($missingFin['is_profitable']['value']);
        $this->assertContains('verified_selling_price', $missingSellingContext['data_truth_meta']['missing_critical_data']);

        // 2. Explicit selling_price supplied -> preserves normal CALCULATED metrics
        $explicitSellingContext = $this->builder->build($listing, [
            'purchase_price' => 350.00,
            'selling_price' => 599.99,
        ]);
        $explicitFin = $explicitSellingContext['financial_analysis'];

        $this->assertSame('599.9900', $explicitFin['selling_price']['value']);
        $this->assertSame(DataTruthLevel::CALCULATED, $explicitFin['selling_price']['truth']);
        $this->assertSame('249.9900', $explicitFin['estimated_profit']['value']);
        $this->assertSame(DataTruthLevel::CALCULATED, $explicitFin['estimated_profit']['truth']);
        $this->assertTrue($explicitFin['is_profitable']['value']);
        $this->assertIsFloat($explicitFin['profit_margin_percent']['value']);
        $this->assertIsFloat($explicitFin['return_on_investment_percent']['value']);
        $this->assertNotContains('verified_selling_price', $explicitSellingContext['data_truth_meta']['missing_critical_data']);
    }

    public function test_user_or_default_shipping_input_without_verified_source_remains_estimated_and_unverified(): void
    {
        $category = Category::factory()->create(['name' => 'Power Banks']);
        $source = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Anker 737 Power Bank',
            'slug' => 'anker-737-e622',
            'brand' => 'Anker',
            'base_currency' => 'USD',
        ]);

        ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'ANKER-737-01',
            'title' => 'Anker 737 Power Bank',
            'price' => 99.50,
            'currency' => 'USD',
            'is_available' => true,
            'url' => 'https://example.com/anker737',
        ]);

        $context = $this->builder->build($canonical, [
            'purchase_price' => 99.50,
            'selling_price' => 149.99,
            'shipping_cost' => 15.00,
            'estimated_fees' => 10.00,
        ]);

        $fin = $context['financial_analysis'];
        $this->assertSame(DataTruthLevel::ESTIMATED, $fin['purchase_price']['truth']);
        $this->assertSame(DataTruthLevel::CALCULATED, $fin['selling_price']['truth']);
        $this->assertSame(DataTruthLevel::ESTIMATED, $fin['shipping_cost']['truth']);
        $this->assertFalse($fin['shipping_cost']['is_verified']);
        $this->assertSame(DataTruthLevel::ESTIMATED, $fin['estimated_fees']['truth']);
        $this->assertSame('ESTIMATED_PENDING_SHIPPING', $fin['estimated_profit']['status']);
        $this->assertContains('verified_shipping_cost_estimate', $context['data_truth_meta']['missing_critical_data']);
    }
}

