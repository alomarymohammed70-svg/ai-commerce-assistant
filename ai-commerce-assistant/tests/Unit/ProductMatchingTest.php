<?php

namespace Tests\Unit;

use App\DTOs\Matching\ProductMatchResult;
use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Services\Matching\ProductMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected ProductMatchingService $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new ProductMatchingService();
    }

    public function test_exact_model_and_brand_returns_match(): void
    {
        $category = Category::factory()->create(['slug' => 'headphones']);

        $canonical = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Sony WH-1000XM4 Wireless Headphones',
            'brand' => 'Sony',
            'model_number' => 'WH-1000XM4',
            'slug' => 'sony-wh-1000xm4',
        ]);

        $result = $this->matcher->matchAttributes(
            title: 'Sony WH-1000XM4 Wireless Noise Cancelling Headphones - Black',
            brand: 'Sony',
            modelNumber: 'WH-1000XM4',
            categoryId: $category->id
        );

        $this->assertEquals(ProductMatchResult::MATCH, $result->status);
        $this->assertTrue($result->isMatch());
        $this->assertGreaterThanOrEqual(0.80, $result->confidenceScore);
        $this->assertNotNull($result->canonicalProduct);
        $this->assertEquals($canonical->id, $result->canonicalProduct->id);
    }

    public function test_brand_with_partial_token_overlap_returns_possible_match(): void
    {
        $canonical = CanonicalProduct::create([
            'name' => 'Anker 737 Power Bank 24000mAh',
            'brand' => 'Anker',
            'slug' => 'anker-737-power-bank',
        ]);

        $result = $this->matcher->matchAttributes(
            title: 'Anker Portable Battery Charger High Speed Power Bank',
            brand: 'Anker'
        );

        $this->assertEquals(ProductMatchResult::POSSIBLE_MATCH, $result->status);
        $this->assertTrue($result->isPossibleMatch());
        $this->assertGreaterThanOrEqual(0.50, $result->confidenceScore);
        $this->assertLessThan(0.80, $result->confidenceScore);
        $this->assertNotNull($result->canonicalProduct);
    }

    public function test_conflicting_brands_returns_no_match(): void
    {
        CanonicalProduct::create([
            'name' => 'Apple AirPods Pro 2nd Generation',
            'brand' => 'Apple',
            'slug' => 'apple-airpods-pro-2',
        ]);

        $result = $this->matcher->matchAttributes(
            title: 'Samsung Galaxy Buds 2 Pro Wireless Earbuds',
            brand: 'Samsung'
        );

        $this->assertEquals(ProductMatchResult::NO_MATCH, $result->status);
        $this->assertTrue($result->isNoMatch());
        $this->assertNull($result->canonicalProduct);
        $this->assertLessThan(0.50, $result->confidenceScore);
    }

    public function test_no_existing_candidates_returns_no_match(): void
    {
        $result = $this->matcher->matchAttributes(
            title: 'Completely Unique Unknown Product',
            brand: 'NonExistentBrand'
        );

        $this->assertEquals(ProductMatchResult::NO_MATCH, $result->status);
        $this->assertTrue($result->isNoMatch());
        $this->assertEquals(0.0, $result->confidenceScore);
    }
}

