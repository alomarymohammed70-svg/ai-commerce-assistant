<?php

namespace Tests\Feature\Api\V1;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CanonicalProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_canonical_products_with_listings_count(): void
    {
        $category = Category::factory()->create();
        $source = ProductSource::factory()->create(['code' => 'amazon']);

        $canonical = CanonicalProduct::factory()->create([
            'category_id' => $category->id,
            'name' => 'Flagship Wireless Headphones',
        ]);

        ProductListing::factory()->count(3)->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'price' => 199.99,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Products retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'brand',
                        'base_currency',
                        'listings_count',
                        'price',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals(3, $data[0]['listings_count']);
    }

    public function test_can_fetch_canonical_product_listings_endpoint(): void
    {
        $canonical = CanonicalProduct::factory()->create();
        $amazon = ProductSource::factory()->create(['code' => 'amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba']);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 299.00,
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 150.00,
        ]);

        $response = $this->getJson("/api/v1/products/{$canonical->id}/listings");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product listings retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'canonical_product_id',
                        'external_id',
                        'title',
                        'price',
                        'currency',
                        'source' => ['id', 'code', 'name'],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_fetch_price_comparison_endpoint(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Sony Noise Canceling Headphones',
            'base_currency' => 'USD',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba']);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 300.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 100.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        $response = $this->getJson("/api/v1/products/{$canonical->id}/price-comparison");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Price comparison completed successfully',
                'data' => [
                    'canonical_product_id' => $canonical->id,
                    'total_offers' => 2,
                    'average_price' => '200.0000',
                    'price_spread' => '200.0000',
                    'price_spread_percentage' => 200.0,
                    'potential_gross_margin_percentage' => 66.67,
                    'lowest_offer' => [
                        'normalized_price' => '100.0000',
                    ],
                    'highest_offer' => [
                        'normalized_price' => '300.0000',
                    ],
                ],
            ]);
    }

    public function test_can_fetch_marketplace_listings_independently(): void
    {
        $listing = ProductListing::factory()->create(['title' => 'Individual Listing Title']);

        $response = $this->getJson('/api/v1/listings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'external_id', 'title', 'price', 'source'],
                ],
            ]);

        $showResponse = $this->getJson("/api/v1/listings/{$listing->id}");
        $showResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $listing->id,
                    'title' => 'Individual Listing Title',
                ],
            ]);
    }

    public function test_raw_payload_is_suppressed_by_default_unless_explicitly_requested(): void
    {
        $listing = ProductListing::factory()->create([
            'raw_payload' => ['secret_api_header' => 'sensitive_key', 'debug' => true],
        ]);

        // Default request: raw_payload should be suppressed
        $response = $this->getJson("/api/v1/listings/{$listing->id}");
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('raw_payload', $response->json('data'));

        // Request with ?include_raw=true: raw_payload is included
        $responseWithRaw = $this->getJson("/api/v1/listings/{$listing->id}?include_raw=true");
        $responseWithRaw->assertStatus(200);
        $this->assertArrayHasKey('raw_payload', $responseWithRaw->json('data'));
        $this->assertEquals('sensitive_key', $responseWithRaw->json('data.raw_payload.secret_api_header'));
    }

    public function test_eager_loading_prevents_n_plus_one_on_price_comparison(): void
    {
        $canonical = CanonicalProduct::factory()->create();
        $source = ProductSource::factory()->create();

        ProductListing::factory()->count(10)->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson("/api/v1/products/{$canonical->id}/price-comparison");

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query 1: Find canonical product
        // Query 2: Eager load listings with source
        // Query count should be strictly small (<= 3), never 10+
        $this->assertLessThanOrEqual(3, $queryCount);
    }
}

