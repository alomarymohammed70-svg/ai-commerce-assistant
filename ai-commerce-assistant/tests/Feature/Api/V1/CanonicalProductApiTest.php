<?php

namespace Tests\Feature\Api\V1;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductMetric;
use App\Models\ProductSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CanonicalProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_canonical_products_with_unified_contract(): void
    {
        $category = Category::factory()->create();
        $source = ProductSource::factory()->create(['code' => 'amazon']);

        $canonical = CanonicalProduct::factory()->create([
            'category_id' => $category->id,
            'name' => 'Flagship Wireless Headphones',
            'base_currency' => 'USD',
        ]);

        ProductListing::factory()->count(3)->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'price' => 199.99,
            'currency' => 'USD',
            'is_available' => true,
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
                        'model_number',
                        'description',
                        'base_currency',
                        'category' => ['id', 'name', 'slug'],
                        'price_summary' => [
                            'lowest_price',
                            'highest_price',
                            'average_price',
                            'currency',
                        ],
                        'active_listings_count',
                        'total_listings_count',
                        'rating',
                        'reviews_count',
                        'latest_opportunity_score',
                        'latest_metric',
                        'listings',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'has_more',
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertSame(3, $data[0]['active_listings_count']);
        $this->assertSame(3, $data[0]['total_listings_count']);
        $this->assertSame([
            'lowest_price' => '199.9900',
            'highest_price' => '199.9900',
            'average_price' => '199.9900',
            'currency' => 'USD',
        ], $data[0]['price_summary']);

        // Legacy compatibility aliases must be completely removed
        $this->assertArrayNotHasKey('title', $data[0]);
        $this->assertArrayNotHasKey('currency', $data[0]);
        $this->assertArrayNotHasKey('price', $data[0]);
        $this->assertArrayNotHasKey('listings_count', $data[0]);
        $this->assertArrayNotHasKey('listings_summary', $data[0]);
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

    public function test_can_filter_canonical_products_by_brand_and_canonical_category_id_or_slug(): void
    {
        $electronics = Category::factory()->create(['slug' => 'electronics']);
        $audio = Category::factory()->create(['slug' => 'audio']);
        $source = ProductSource::factory()->create(['code' => 'ebay']);

        $sonyProduct = CanonicalProduct::factory()->create([
            'category_id' => $electronics->id,
            'name' => 'Sony WH-1000XM5',
            'brand' => 'Sony',
        ]);
        ProductListing::factory()->create([
            'canonical_product_id' => $sonyProduct->id,
            'source_id' => $source->id,
            'category_id' => $electronics->id,
            'brand' => 'Sony',
            'price' => 349.00,
        ]);

        $ankerProduct = CanonicalProduct::factory()->create([
            'category_id' => $audio->id,
            'name' => 'Anker Soundcore Liberty 4',
            'brand' => 'Anker',
        ]);
        ProductListing::factory()->create([
            'canonical_product_id' => $ankerProduct->id,
            'source_id' => $source->id,
            'category_id' => $audio->id,
            'brand' => 'Anker',
            'price' => 129.00,
        ]);

        // Filter by brand
        $brandRes = $this->getJson('/api/v1/products?brand=Sony');
        $brandRes->assertStatus(200);
        $this->assertCount(1, $brandRes->json('data'));
        $this->assertSame($sonyProduct->id, $brandRes->json('data.0.id'));

        // Filter by canonical category using numeric ID
        $catIdRes = $this->getJson("/api/v1/products?category={$audio->id}");
        $catIdRes->assertStatus(200);
        $this->assertCount(1, $catIdRes->json('data'));
        $this->assertSame($ankerProduct->id, $catIdRes->json('data.0.id'));

        // Filter by canonical category using slug
        $catSlugRes = $this->getJson('/api/v1/products?category=electronics');
        $catSlugRes->assertStatus(200);
        $this->assertCount(1, $catSlugRes->json('data'));
        $this->assertSame($sonyProduct->id, $catSlugRes->json('data.0.id'));
    }

    public function test_canonical_products_sorting_supports_validated_columns_and_rejects_invalid_with_422(): void
    {
        $source = ProductSource::factory()->create(['code' => 'amazon']);

        $beta = CanonicalProduct::factory()->create(['name' => 'Beta Device', 'brand' => 'ZetaBrand']);
        ProductListing::factory()->create([
            'canonical_product_id' => $beta->id,
            'source_id' => $source->id,
            'price' => 250.00,
            'rating' => 4.9,
            'reviews_count' => 10,
            'is_available' => true,
        ]);

        $alpha = CanonicalProduct::factory()->create(['name' => 'Alpha Device', 'brand' => 'Anker']);
        ProductListing::factory()->create([
            'canonical_product_id' => $alpha->id,
            'source_id' => $source->id,
            'price' => 80.00,
            'rating' => 3.5,
            'reviews_count' => 500,
            'is_available' => true,
        ]);

        // Sort by name asc -> Alpha first
        $nameAsc = $this->getJson('/api/v1/products?sort_by=name&sort_direction=asc');
        $nameAsc->assertStatus(200);
        $this->assertSame([$alpha->id, $beta->id], array_column($nameAsc->json('data'), 'id'));

        // Sort by brand desc -> ZetaBrand (Beta) first
        $brandDesc = $this->getJson('/api/v1/products?sort_by=brand&sort_direction=desc');
        $brandDesc->assertStatus(200);
        $this->assertSame([$beta->id, $alpha->id], array_column($brandDesc->json('data'), 'id'));

        // Sort by price asc -> Alpha ($80) first
        $priceAsc = $this->getJson('/api/v1/products?sort_by=price&sort_direction=asc');
        $priceAsc->assertStatus(200);
        $this->assertSame([$alpha->id, $beta->id], array_column($priceAsc->json('data'), 'id'));

        // Sort by rating desc -> Beta (4.9) first
        $ratingDesc = $this->getJson('/api/v1/products?sort_by=rating&sort_direction=desc');
        $ratingDesc->assertStatus(200);
        $this->assertSame([$beta->id, $alpha->id], array_column($ratingDesc->json('data'), 'id'));

        // Sort by reviews_count desc -> Alpha (500) first
        $reviewsDesc = $this->getJson('/api/v1/products?sort_by=reviews_count&sort_direction=desc');
        $reviewsDesc->assertStatus(200);
        $this->assertSame([$alpha->id, $beta->id], array_column($reviewsDesc->json('data'), 'id'));

        // Reject unsupported sort_by with 422 instead of silent fallback
        $invalidTitle = $this->getJson('/api/v1/products?sort_by=title');
        $invalidTitle->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);

        $invalidRandom = $this->getJson('/api/v1/products?sort_by=nonexistent');
        $invalidRandom->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_listings_endpoint_supports_brand_filter_and_rejects_invalid_sort_by(): void
    {
        $sony = ProductListing::factory()->create(['brand' => 'Sony', 'title' => 'Sony Buds', 'price' => 199.00]);
        $bose = ProductListing::factory()->create(['brand' => 'Bose', 'title' => 'Bose QC', 'price' => 299.00]);

        $brandRes = $this->getJson('/api/v1/listings?brand=Bose');
        $brandRes->assertStatus(200);
        $this->assertCount(1, $brandRes->json('data'));
        $this->assertSame($bose->id, $brandRes->json('data.0.id'));

        $sortRes = $this->getJson('/api/v1/listings?sort_by=price&sort_direction=asc');
        $sortRes->assertStatus(200);
        $this->assertSame([$sony->id, $bose->id], array_column($sortRes->json('data'), 'id'));

        // Reject canonical-only 'name' on /listings with 422 instead of silent fallback
        $this->getJson('/api/v1/listings?sort_by=name')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_price_comparison_supports_target_currency_conversion_and_rejects_invalid_currency(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Multi-Currency Studio Headphones',
            'base_currency' => 'USD',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba', 'name' => 'Alibaba Wholesale']);

        // 100.00 USD -> 92.5000 EUR (rate 0.925000)
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 100.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // 144.80 CNY -> 20.00 USD -> 18.5000 EUR (rate 0.127762)
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 144.8000,
            'currency' => 'CNY',
            'is_available' => true,
        ]);

        // Lowercase target_currency should be normalized to uppercase by PriceComparisonRequest
        $response = $this->getJson("/api/v1/products/{$canonical->id}/price-comparison?target_currency=eur");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Price comparison completed successfully',
                'data' => [
                    'canonical_product_id' => $canonical->id,
                    'canonical_product_name' => 'Multi-Currency Studio Headphones',
                    'target_currency' => 'EUR',
                    'total_offers' => 2,
                    'average_price' => '55.5000',
                    'price_spread' => '74.0000',
                    'price_spread_percentage' => 400.0,
                    'potential_gross_margin_percentage' => 80.0,
                    'lowest_offer' => [
                        'original_price' => '144.8000',
                        'original_currency' => 'CNY',
                        'normalized_price' => '18.5000',
                        'target_currency' => 'EUR',
                        'exchange_rate' => '0.127762',
                    ],
                    'highest_offer' => [
                        'original_price' => '100.0000',
                        'original_currency' => 'USD',
                        'normalized_price' => '92.5000',
                        'target_currency' => 'EUR',
                        'exchange_rate' => '0.925000',
                    ],
                ],
            ]);

        // Invalid target currency must fail strictly with 422
        $invalidResponse = $this->getJson("/api/v1/products/{$canonical->id}/price-comparison?target_currency=XYZ");
        $invalidResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['target_currency']);
    }

    public function test_price_comparison_exposes_safe_seller_summary_without_raw_payload_and_null_when_absent(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Seller Signal Comparison Product',
            'base_currency' => 'USD',
        ]);

        $ebay = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);
        $amazon = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba', 'name' => 'Alibaba Wholesale']);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 150.0000,
            'currency' => 'USD',
            'rating' => 4.60,
            'reviews_count' => 120,
            'is_available' => true,
            'raw_payload' => [
                'company_name' => 'Shenzhen Audio Co.',
                'secret_api_header' => 'TOP_SECRET',
            ],
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 220.0000,
            'currency' => 'USD',
            'rating' => null,
            'reviews_count' => 45,
            'is_available' => true,
            'raw_payload' => ['mock' => true],
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 250.0000,
            'currency' => 'USD',
            'rating' => 4.84,
            'reviews_count' => 310,
            'is_available' => true,
            'raw_payload' => [
                'seller' => [
                    'username' => 'TechStoreUSA',
                    'feedbackPercentage' => 99.4,
                    'feedbackScore' => 4200,
                ],
                'secret_api_header' => 'TOP_SECRET',
            ],
        ]);

        $response = $this->getJson("/api/v1/products/{$canonical->id}/price-comparison");

        $response->assertStatus(200);
        $rawContent = $response->getContent();
        $this->assertStringNotContainsString('raw_payload', $rawContent);
        $this->assertStringNotContainsString('TOP_SECRET', $rawContent);

        $data = $response->json('data');
        $this->assertSame(3, $data['total_offers']);
        $this->assertCount(3, $data['offers']);

        $offersByCode = [];
        foreach ($data['offers'] as $offer) {
            $this->assertArrayNotHasKey('raw_payload', $offer);
            $this->assertArrayHasKey('source', $offer);
            $this->assertArrayHasKey('id', $offer['source']);
            $this->assertArrayHasKey('code', $offer['source']);
            $this->assertArrayHasKey('name', $offer['source']);
            $offersByCode[$offer['source']['code']] = $offer;
        }

        $this->assertSame([
            'username' => null,
            'store_name' => 'Shenzhen Audio Co.',
            'feedback_percentage' => null,
            'feedback_score' => null,
        ], $offersByCode['alibaba']['seller']);

        $this->assertNull($offersByCode['amazon']['seller']);
        $this->assertNull($offersByCode['amazon']['rating']);

        $this->assertSame([
            'username' => 'TechStoreUSA',
            'store_name' => null,
            'feedback_percentage' => 99.4,
            'feedback_score' => 4200,
        ], $offersByCode['ebay']['seller']);
    }

    public function test_price_comparison_handles_zero_active_offers_cleanly(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Unavailable Audio Product',
            'base_currency' => 'USD',
        ]);

        $source = ProductSource::factory()->create(['code' => 'amazon']);
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $source->id,
            'price' => 99.9900,
            'currency' => 'USD',
            'is_available' => false,
        ]);

        $response = $this->getJson("/api/v1/products/{$canonical->id}/price-comparison");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Price comparison completed successfully',
                'data' => [
                    'canonical_product_id' => $canonical->id,
                    'canonical_product_name' => 'Unavailable Audio Product',
                    'target_currency' => 'USD',
                    'total_offers' => 0,
                    'lowest_offer' => null,
                    'highest_offer' => null,
                    'average_price' => '0.0000',
                    'price_spread' => '0.0000',
                    'price_spread_percentage' => 0.0,
                    'potential_gross_margin_percentage' => 0.0,
                    'offers' => [],
                ],
            ]);
    }

    public function test_canonical_product_details_matches_canonical_list_contract_and_includes_metrics_history(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        $canonical = CanonicalProduct::factory()->create([
            'category_id' => $category->id,
            'name' => 'Sony WH-1000XM5',
            'brand' => 'Sony',
            'base_currency' => 'USD',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);
        $ebay = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);

        $listing1 = ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 280.0000,
            'currency' => 'USD',
            'rating' => 4.80,
            'reviews_count' => 120,
            'is_available' => true,
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 340.0000,
            'currency' => 'USD',
            'rating' => 4.60,
            'reviews_count' => 80,
            'is_available' => true,
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 150.0000,
            'currency' => 'USD',
            'rating' => null,
            'reviews_count' => 0,
            'is_available' => false,
        ]);

        ProductMetric::factory()->create([
            'canonical_product_id' => $canonical->id,
            'product_id' => $listing1->id,
            'user_id' => null,
            'opportunity_score' => 78.50,
            'risk_score' => 24.00,
        ]);

        $response = $this->getJson("/api/v1/products/{$canonical->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product details retrieved successfully',
                'data' => [
                    'id' => $canonical->id,
                    'name' => 'Sony WH-1000XM5',
                    'brand' => 'Sony',
                    'base_currency' => 'USD',
                    'category' => [
                        'id' => $category->id,
                        'name' => 'Electronics',
                    ],
                    'price_summary' => [
                        'lowest_price' => '280.0000',
                        'highest_price' => '340.0000',
                        'average_price' => '310.0000',
                        'currency' => 'USD',
                    ],
                    'active_listings_count' => 2,
                    'total_listings_count' => 3,
                    'rating' => 4.7,
                    'reviews_count' => 200,
                    'latest_opportunity_score' => 78.5,
                    'latest_metric' => [
                        'opportunity_score' => '78.50',
                        'risk_score' => '24.00',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'metrics_history',
                    'listings' => [
                        '*' => ['id', 'external_id', 'title', 'price', 'currency', 'seller', 'source'],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data['metrics_history']);
        $this->assertArrayNotHasKey('title', $data);
        $this->assertArrayNotHasKey('currency', $data);
        $this->assertArrayNotHasKey('price', $data);
        $this->assertArrayNotHasKey('listings_count', $data);
        $this->assertArrayNotHasKey('listings_summary', $data);
    }

    public function test_canonical_product_empty_or_unavailable_listings_return_null_prices_never_zero(): void
    {
        $source = ProductSource::factory()->create(['code' => 'amazon']);

        $noListings = CanonicalProduct::factory()->create([
            'category_id' => null,
            'name' => 'No Listings Product',
            'base_currency' => 'EUR',
        ]);

        $onlyInactive = CanonicalProduct::factory()->create([
            'name' => 'Only Inactive Listing Product',
            'base_currency' => 'USD',
        ]);
        ProductListing::factory()->create([
            'canonical_product_id' => $onlyInactive->id,
            'source_id' => $source->id,
            'price' => 59.9900,
            'currency' => 'USD',
            'is_available' => false,
        ]);

        $genuineZero = CanonicalProduct::factory()->create([
            'name' => 'Genuine Zero Promo Offer',
            'base_currency' => 'USD',
        ]);
        ProductListing::factory()->create([
            'canonical_product_id' => $genuineZero->id,
            'source_id' => $source->id,
            'price' => 0.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // List endpoint check
        $listRes = $this->getJson('/api/v1/products?per_page=10&sort_by=created_at&sort_direction=asc');
        $listRes->assertStatus(200);
        $itemsById = collect($listRes->json('data'))->keyBy('id');

        $this->assertNull($itemsById[$noListings->id]['category']);
        $this->assertSame([
            'lowest_price' => null,
            'highest_price' => null,
            'average_price' => null,
            'currency' => 'EUR',
        ], $itemsById[$noListings->id]['price_summary']);
        $this->assertSame(0, $itemsById[$noListings->id]['active_listings_count']);
        $this->assertSame(0, $itemsById[$noListings->id]['total_listings_count']);
        $this->assertNull($itemsById[$noListings->id]['latest_opportunity_score']);

        $this->assertSame([
            'lowest_price' => null,
            'highest_price' => null,
            'average_price' => null,
            'currency' => 'USD',
        ], $itemsById[$onlyInactive->id]['price_summary']);
        $this->assertSame(0, $itemsById[$onlyInactive->id]['active_listings_count']);
        $this->assertSame(1, $itemsById[$onlyInactive->id]['total_listings_count']);

        // Genuine 0.0000 active offer stays '0.0000', clearly distinct from null
        $this->assertSame([
            'lowest_price' => '0.0000',
            'highest_price' => '0.0000',
            'average_price' => '0.0000',
            'currency' => 'USD',
        ], $itemsById[$genuineZero->id]['price_summary']);
        $this->assertSame(1, $itemsById[$genuineZero->id]['active_listings_count']);

        // Details endpoint parity check on empty / inactive
        $detailNoListings = $this->getJson("/api/v1/products/{$noListings->id}");
        $detailNoListings->assertStatus(200);
        $this->assertSame([
            'lowest_price' => null,
            'highest_price' => null,
            'average_price' => null,
            'currency' => 'EUR',
        ], $detailNoListings->json('data.price_summary'));
        $this->assertSame(0, $detailNoListings->json('data.active_listings_count'));
        $this->assertNull($detailNoListings->json('data.latest_opportunity_score'));
    }

    public function test_canonical_product_price_summary_normalizes_multi_currency_listings_to_base_currency(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Multi-Currency Normalized Product',
            'base_currency' => 'EUR',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba']);
        $ebay = ProductSource::factory()->create(['code' => 'ebay']);

        // 100.00 USD -> 92.5000 EUR
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 100.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // 144.80 CNY -> 20.00 USD -> 18.5000 EUR
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 144.8000,
            'currency' => 'CNY',
            'is_available' => true,
        ]);

        // Unsupported currency XYZ -> must be safely skipped rather than mixing 999.00 as EUR
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 999.0000,
            'currency' => 'XYZ',
            'is_available' => true,
        ]);

        // Unavailable listing -> excluded from active count and price summary
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 5.0000,
            'currency' => 'EUR',
            'is_available' => false,
        ]);

        $expectedSummary = [
            'lowest_price' => '18.5000',
            'highest_price' => '92.5000',
            'average_price' => '55.5000',
            'currency' => 'EUR',
        ];

        $listRes = $this->getJson('/api/v1/products');
        $listRes->assertStatus(200);
        $this->assertSame($expectedSummary, $listRes->json('data.0.price_summary'));
        $this->assertSame(3, $listRes->json('data.0.active_listings_count'));
        $this->assertSame(4, $listRes->json('data.0.total_listings_count'));

        $detailRes = $this->getJson("/api/v1/products/{$canonical->id}");
        $detailRes->assertStatus(200);
        $this->assertSame($expectedSummary, $detailRes->json('data.price_summary'));
        $this->assertSame(3, $detailRes->json('data.active_listings_count'));
        $this->assertSame(4, $detailRes->json('data.total_listings_count'));
    }

    public function test_canonical_product_opportunity_score_enforces_user_metric_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $canonical = CanonicalProduct::factory()->create(['name' => 'Isolated Opportunity Product']);
        $listing = ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'price' => 50.0000,
            'is_available' => true,
        ]);

        $now = now()->startOfSecond();

        // User A creates a private metric (no shared metric exists yet)
        ProductMetric::factory()->create([
            'canonical_product_id' => $canonical->id,
            'product_id' => $listing->id,
            'user_id' => $userA->id,
            'opportunity_score' => 91.50,
            'calculated_at' => $now->copy()->addSeconds(5),
        ]);

        // 1. User A sees their private opportunity score on both list and details
        Sanctum::actingAs($userA);
        $this->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonPath('data.0.latest_opportunity_score', 91.5);
        $this->getJson("/api/v1/products/{$canonical->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.latest_opportunity_score', 91.5);

        // 2. User B must see null (not 0 and never 91.5)
        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonPath('data.0.latest_opportunity_score', null)
            ->assertJsonPath('data.0.latest_metric', null);
        $this->getJson("/api/v1/products/{$canonical->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.latest_opportunity_score', null)
            ->assertJsonPath('data.latest_metric', null);

        // 3. Shared baseline metric is added -> User B and Guest see 52.25; User A still sees newer 91.5
        ProductMetric::factory()->create([
            'canonical_product_id' => $canonical->id,
            'product_id' => $listing->id,
            'user_id' => null,
            'opportunity_score' => 52.25,
            'calculated_at' => $now,
        ]);

        Sanctum::actingAs($userB);
        $this->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonPath('data.0.latest_opportunity_score', 52.25);
        $this->getJson("/api/v1/products/{$canonical->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.latest_opportunity_score', 52.25);

        Sanctum::actingAs($userA);
        $this->getJson("/api/v1/products/{$canonical->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.latest_opportunity_score', 91.5);

        // 4. Guest sees only shared baseline
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->getJson("/api/v1/products/{$canonical->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.latest_opportunity_score', 52.25);
    }

    public function test_canonical_product_listings_expose_safe_seller_without_raw_payload(): void
    {
        $canonical = CanonicalProduct::factory()->create(['name' => 'Seller Privacy Product']);
        $ebay = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay Marketplace']);
        $amazon = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 120.0000,
            'is_available' => true,
            'raw_payload' => [
                'seller' => [
                    'username' => 'VerifiedHub',
                    'feedbackPercentage' => 99.1,
                    'feedbackScore' => 3100,
                ],
                'secret_token' => 'LEAK_CHECK',
            ],
        ]);

        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 140.0000,
            'is_available' => true,
            'raw_payload' => ['mock' => true],
        ]);

        $detailRes = $this->getJson("/api/v1/products/{$canonical->id}");
        $detailRes->assertStatus(200);
        $rawContent = $detailRes->getContent();
        $this->assertStringNotContainsString('raw_payload', $rawContent);
        $this->assertStringNotContainsString('LEAK_CHECK', $rawContent);

        $listings = collect($detailRes->json('data.listings'))->keyBy(fn ($item) => $item['source']['code']);
        $this->assertSame([
            'username' => 'VerifiedHub',
            'store_name' => null,
            'feedback_percentage' => 99.1,
            'feedback_score' => 3100,
        ], $listings['ebay']['seller']);
        $this->assertNull($listings['amazon']['seller']);
        $this->assertArrayNotHasKey('raw_payload', $listings['ebay']);
        $this->assertArrayNotHasKey('raw_payload', $listings['amazon']);

        // List endpoint also exposes safe seller and suppresses raw_payload
        $listRes = $this->getJson('/api/v1/products');
        $listRes->assertStatus(200);
        $this->assertStringNotContainsString('raw_payload', $listRes->getContent());
        $this->assertStringNotContainsString('LEAK_CHECK', $listRes->getContent());
    }
}

