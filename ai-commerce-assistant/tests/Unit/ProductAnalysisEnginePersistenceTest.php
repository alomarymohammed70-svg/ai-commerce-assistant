<?php

namespace Tests\Unit;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Models\User;
use App\Services\Analysis\ProductAnalysisEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnalysisEnginePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductAnalysisEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ProductAnalysisEngine::class);
    }

    public function test_case_a_calculate_and_persist_without_selling_price_never_fabricates_markup_or_profit_metrics(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $source = ProductSource::factory()->create();

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'No Selling Price Canonical',
            'slug' => 'no-selling-price-canonical',
            'base_currency' => 'USD',
        ]);

        $listing = ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'UNIT-NO-SELL-1',
            'title' => 'No Selling Price Listing',
            'price' => 100.0000,
            'original_price' => 180.0000,
            'currency' => 'USD',
            'rating' => 4.5,
            'reviews_count' => 100,
            'is_available' => true,
            'url' => 'https://example.com/item-1',
        ]);

        // 1. CanonicalProduct with empty params ({}): must NOT fabricate 100 * 1.50 = 150.0000
        $canonicalMetric = $this->engine->calculateAndPersist($canonical, [], $user->id);

        $this->assertSame('100.0000', $canonicalMetric->purchase_price);
        $this->assertNull($canonicalMetric->selling_price);
        $this->assertNull($canonicalMetric->estimated_profit);
        $this->assertNull($canonicalMetric->profit_margin);
        $this->assertNull($canonicalMetric->return_on_investment);
        $this->assertArrayHasKey('is_profitable', $canonicalMetric->calculation_notes);
        $this->assertNull($canonicalMetric->calculation_notes['is_profitable']);
        $this->assertNotTrue($canonicalMetric->calculation_notes['is_profitable']);
        $this->assertSame('DATA_UNAVAILABLE', $canonicalMetric->calculation_notes['status'] ?? null);
        $this->assertSame(['selling_price'], $canonicalMetric->calculation_notes['missing_critical_data'] ?? null);

        $this->assertDatabaseHas('product_metrics', [
            'id' => $canonicalMetric->id,
            'canonical_product_id' => $canonical->id,
            'purchase_price' => '100.0000',
            'selling_price' => null,
            'estimated_profit' => null,
            'profit_margin' => null,
            'return_on_investment' => null,
        ]);

        // 2. ProductListing directly without selling_price (even when original_price=180 exists and selling_price=null):
        // must NOT fall back to original_price (180) or 1.50x (150) or 0
        $listingMetric = $this->engine->calculateAndPersist($listing, [
            'purchase_price' => 100.00,
            'selling_price' => null,
        ], $user->id);

        $this->assertSame('100.0000', $listingMetric->purchase_price);
        $this->assertNull($listingMetric->selling_price);
        $this->assertNull($listingMetric->estimated_profit);
        $this->assertNull($listingMetric->profit_margin);
        $this->assertNull($listingMetric->return_on_investment);
        $this->assertArrayHasKey('is_profitable', $listingMetric->calculation_notes);
        $this->assertNull($listingMetric->calculation_notes['is_profitable']);
        $this->assertSame('DATA_UNAVAILABLE', $listingMetric->calculation_notes['status'] ?? null);
    }

    public function test_case_b_calculate_and_persist_with_explicit_selling_price_preserves_deterministic_calculations(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $source = ProductSource::factory()->create();

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Explicit Selling Price Canonical',
            'slug' => 'explicit-selling-price-canonical',
            'base_currency' => 'USD',
        ]);

        ProductListing::create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'UNIT-EXPLICIT-1',
            'title' => 'Explicit Selling Price Listing',
            'price' => 100.0000,
            'currency' => 'USD',
            'rating' => 4.5,
            'reviews_count' => 100,
            'is_available' => true,
            'url' => 'https://example.com/item-2',
        ]);

        $metric = $this->engine->calculateAndPersist($canonical, [
            'selling_price' => 150.00,
        ], $user->id);

        $this->assertSame('100.0000', $metric->purchase_price);
        $this->assertSame('150.0000', $metric->selling_price);
        $this->assertSame('50.0000', $metric->estimated_profit);
        $this->assertSame('33.3333', $metric->profit_margin);
        $this->assertSame('50.0000', $metric->return_on_investment);
        $this->assertTrue($metric->calculation_notes['is_profitable'] ?? false);

        $this->assertDatabaseHas('product_metrics', [
            'id' => $metric->id,
            'selling_price' => '150.0000',
            'purchase_price' => '100.0000',
            'estimated_profit' => '50.0000',
            'profit_margin' => '33.3333',
            'return_on_investment' => '50.0000',
        ]);
    }
}

