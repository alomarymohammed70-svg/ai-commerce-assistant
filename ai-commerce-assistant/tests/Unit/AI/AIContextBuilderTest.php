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
        // Margin = (58 / 220) * 100 = 26.3636%
        $fin = $context['financial_analysis'];
        $this->assertEquals('162.0000', $fin['total_cost']['value']);
        $this->assertEquals(DataTruthLevel::CALCULATED, $fin['total_cost']['truth']);
        $this->assertEquals('58.0000', $fin['estimated_profit']['value']);
        $this->assertEquals(DataTruthLevel::CALCULATED, $fin['estimated_profit']['truth']);
        $this->assertEquals('26.3636%', $fin['profit_margin_percent']['value']);
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
}

