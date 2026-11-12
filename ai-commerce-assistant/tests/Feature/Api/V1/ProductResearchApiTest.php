<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMetric;
use App\Models\ProductSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductResearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories_tree(): void
    {
        $parent = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        Category::factory()->create(['name' => 'Headphones', 'slug' => 'headphones', 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Categories retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'children'],
                ],
            ]);
    }

    public function test_can_list_product_sources(): void
    {
        ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon Marketplace']);
        ProductSource::factory()->create(['code' => 'alibaba', 'name' => 'Alibaba Wholesale']);

        $response = $this->getJson('/api/v1/sources');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product sources retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_active'],
                ],
            ]);
    }

    public function test_can_list_products_with_pagination(): void
    {
        $source = ProductSource::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->count(25)->create([
            'source_id' => $source->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/products?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'brand',
                        'base_currency',
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
                        'source',
                        'category',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'has_more',
                ],
            ])
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 25,
                    'last_page' => 3,
                    'has_more' => true,
                ],
            ]);
    }

    public function test_can_filter_products_by_source_and_category(): void
    {
        $amazon = ProductSource::factory()->create(['code' => 'amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba']);

        $electronics = Category::factory()->create(['slug' => 'electronics']);
        $fashion = Category::factory()->create(['slug' => 'fashion']);

        $match = Product::factory()->create([
            'source_id' => $amazon->id,
            'category_id' => $electronics->id,
            'title' => 'Target Match Product',
            'price' => 25.0000,
        ]);

        Product::factory()->create([
            'source_id' => $alibaba->id,
            'category_id' => $fashion->id,
            'title' => 'Other Product',
            'price' => 50.0000,
        ]);

        $response = $this->getJson('/api/v1/products?source=amazon&category=electronics');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($match->id, $data[0]['id']);
    }

    public function test_can_filter_products_by_price_and_rating(): void
    {
        Product::factory()->create([
            'title' => 'Cheap Low Rated',
            'price' => 10.0000,
            'rating' => 2.5,
        ]);

        $highValue = Product::factory()->create([
            'title' => 'Premium High Rated',
            'price' => 85.0000,
            'rating' => 4.8,
        ]);

        $response = $this->getJson('/api/v1/products?min_price=50&min_rating=4.0');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($highValue->id, $data[0]['id']);
    }

    public function test_can_fetch_single_product_details(): void
    {
        $product = Product::factory()->create([
            'title' => 'Detailed Test Product',
            'price' => 49.9900,
        ]);

        ProductMetric::factory()->create([
            'product_id' => $product->id,
            'opportunity_score' => 82.50,
            'risk_score' => 22.00,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->canonical_product_id,
                    'name' => 'Detailed Test Product',
                    'price_summary' => [
                        'lowest_price' => '49.9900',
                        'highest_price' => '49.9900',
                        'average_price' => '49.9900',
                        'currency' => 'USD',
                    ],
                    'active_listings_count' => 1,
                    'total_listings_count' => 1,
                    'latest_opportunity_score' => 82.5,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price_summary' => [
                        'lowest_price',
                        'highest_price',
                        'average_price',
                        'currency',
                    ],
                    'active_listings_count',
                    'total_listings_count',
                    'latest_opportunity_score',
                    'latest_metric' => ['opportunity_score', 'risk_score'],
                    'metrics_history',
                ],
            ]);
    }

    public function test_standalone_analysis_calculation(): void
    {
        $response = $this->postJson('/api/v1/analysis/calculate', [
            'purchase_price' => 12.50,
            'selling_price' => 39.99,
            'shipping_cost' => 4.00,
            'estimated_fees' => 5.50,
            'other_costs' => 1.00,
            'rating' => 4.6,
            'reviews_count' => 420,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Standalone calculation completed successfully',
                'data' => [
                    'is_profitable' => true,
                    'competition_level' => 'medium',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'purchase_price',
                    'selling_price',
                    'total_cost',
                    'estimated_profit',
                    'profit_margin',
                    'return_on_investment',
                    'opportunity_score',
                    'risk_score',
                ],
            ]);
    }

    public function test_product_analyze_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson("/api/v1/products/{$product->id}/analyze", [
            'selling_price' => 49.99,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);
    }

    public function test_authenticated_user_can_analyze_and_persist_product_metric(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create([
            'price' => 15.0000,
            'rating' => 4.5,
            'reviews_count' => 150,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/products/{$product->id}/analyze", [
                'purchase_price' => 15.00,
                'selling_price' => 45.00,
                'shipping_cost' => 5.00,
                'estimated_fees' => 6.00,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product analysis completed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'selling_price',
                    'purchase_price',
                    'estimated_profit',
                    'profit_margin',
                    'opportunity_score',
                    'risk_score',
                ],
            ]);

        $this->assertDatabaseHas('product_metrics', [
            'product_id' => $product->id,
            'selling_price' => '45.0000',
            'purchase_price' => '15.0000',
        ]);
    }

    public function test_product_filter_validation_bounds(): void
    {
        $response = $this->getJson('/api/v1/products?min_price=-50&min_rating=10');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['min_price', 'min_rating']);
    }

    public function test_eager_loading_prevents_n_plus_one_queries(): void
    {
        $source = ProductSource::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->count(10)->create([
            'source_id' => $source->id,
            'category_id' => $category->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/products?per_page=10');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1: count query for paginator
        // 2: select products query
        // 3: eager load sources
        // 4: eager load categories
        // 5: eager load latest metrics
        // Total queries should be constant (~5) and strictly less than 10 products + 1
        $this->assertLessThanOrEqual(6, $queryCount);
    }

    public function test_personal_product_metric_is_owned_by_creator_and_cannot_spoof_user_id(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 15.0000,
            'rating' => 4.5,
            'reviews_count' => 150,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->postJson("/api/v1/products/{$product->canonical_product_id}/analyze", [
            'purchase_price' => 100.00,
            'selling_price' => 200.00,
            'shipping_cost' => 20.00,
            'estimated_fees' => 10.00,
            'user_id' => $userB->id,
        ]);

        $response->assertStatus(201);
        $metricId = $response->json('data.id');

        $this->assertDatabaseHas('product_metrics', [
            'id' => $metricId,
            'user_id' => $userA->id,
            'purchase_price' => '100.0000',
            'selling_price' => '200.0000',
        ]);
        $this->assertDatabaseMissing('product_metrics', [
            'id' => $metricId,
            'user_id' => $userB->id,
        ]);
    }

    public function test_user_b_and_guests_cannot_see_user_a_private_financial_metrics_across_endpoints(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $listing = Product::factory()->create([
            'title' => 'Isolated Financial Product',
            'price' => 50.0000,
        ]);
        $canonicalId = $listing->canonical_product_id;

        // Shared baseline metric (user_id = null) created at same second to test tie-breaking
        $now = now()->startOfSecond();
        $sharedMetric = ProductMetric::factory()->create([
            'canonical_product_id' => $canonicalId,
            'product_id' => $listing->id,
            'user_id' => null,
            'purchase_price' => 40.0000,
            'selling_price' => 80.0000,
            'shipping_cost' => 5.0000,
            'estimated_fees' => 3.0000,
            'calculated_at' => $now,
        ]);

        // User A analyzes the product with sensitive supplier costs (same second & later)
        Sanctum::actingAs($userA);
        $analyzeRes = $this->postJson("/api/v1/products/{$canonicalId}/analyze", [
            'purchase_price' => 100.00,
            'selling_price' => 200.00,
            'shipping_cost' => 20.00,
            'estimated_fees' => 10.00,
        ]);
        $analyzeRes->assertStatus(201);
        $userAMetricId = $analyzeRes->json('data.id');

        // 1. User A sees their own private financial inputs on product details & catalog
        $userADetails = $this->getJson("/api/v1/products/{$canonicalId}");
        $userADetails->assertStatus(200);
        $this->assertSame($userAMetricId, $userADetails->json('data.latest_metric.id'));
        $this->assertSame('100.0000', $userADetails->json('data.latest_metric.purchase_price'));

        // 2. User B must NEVER see User A's private financial inputs anywhere, and MUST still see shared baseline
        Sanctum::actingAs($userB);

        $userBDetails = $this->getJson("/api/v1/products/{$canonicalId}");
        $userBDetails->assertStatus(200);
        $this->assertSame($sharedMetric->id, $userBDetails->json('data.latest_metric.id'));
        $this->assertSame('40.0000', $userBDetails->json('data.latest_metric.purchase_price'));
        $this->assertStringNotContainsString('100.0000', $userBDetails->getContent());
        $this->assertStringNotContainsString('200.0000', $userBDetails->getContent());
        $this->assertCount(1, $userBDetails->json('data.metrics_history'));

        $userBList = $this->getJson('/api/v1/products');
        $userBList->assertStatus(200);
        $this->assertStringNotContainsString('100.0000', $userBList->getContent());
        $this->assertSame($sharedMetric->id, $userBList->json('data.0.latest_metric.id'));

        $userBListings = $this->getJson("/api/v1/products/{$canonicalId}/listings");
        $userBListings->assertStatus(200);
        $this->assertStringNotContainsString('100.0000', $userBListings->getContent());
        $this->assertSame($sharedMetric->id, $userBListings->json('data.0.latest_metric.id'));

        $userBListingShow = $this->getJson("/api/v1/listings/{$listing->id}");
        $userBListingShow->assertStatus(200);
        $this->assertSame($sharedMetric->id, $userBListingShow->json('data.latest_metric.id'));
        $this->assertStringNotContainsString('100.0000', $userBListingShow->getContent());

        // 3. Anonymous guest also sees only shared baseline metric and NEVER User A's data
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $guestDetails = $this->getJson("/api/v1/products/{$canonicalId}");
        $guestDetails->assertStatus(200);
        $this->assertSame($sharedMetric->id, $guestDetails->json('data.latest_metric.id'));
        $this->assertStringNotContainsString('100.0000', $guestDetails->getContent());
        $this->assertCount(1, $guestDetails->json('data.metrics_history'));
    }

    public function test_when_no_shared_metric_exists_user_b_and_guests_receive_null_not_user_a_metric(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $listing = Product::factory()->create(['price' => 30.0000]);
        $canonicalId = $listing->canonical_product_id;

        Sanctum::actingAs($userA);
        $this->postJson("/api/v1/products/{$canonicalId}/analyze", [
            'purchase_price' => 111.00,
            'selling_price' => 222.00,
            'shipping_cost' => 12.00,
            'estimated_fees' => 13.00,
        ])->assertStatus(201);

        // User B creates no analysis yet -> must receive null and empty metrics_history
        Sanctum::actingAs($userB);
        $userBDetails = $this->getJson("/api/v1/products/{$canonicalId}");
        $userBDetails->assertStatus(200);
        $this->assertNull($userBDetails->json('data.latest_metric'));
        $this->assertSame([], $userBDetails->json('data.metrics_history'));
        $this->assertStringNotContainsString('111.0000', $userBDetails->getContent());
        $this->assertStringNotContainsString('222.0000', $userBDetails->getContent());

        // Guest also gets null and empty metrics_history
        $this->app['auth']->forgetGuards();
        $guestDetails = $this->getJson("/api/v1/products/{$canonicalId}");
        $guestDetails->assertStatus(200);
        $this->assertNull($guestDetails->json('data.latest_metric'));
        $this->assertSame([], $guestDetails->json('data.metrics_history'));
        $this->assertStringNotContainsString('111.0000', $guestDetails->getContent());
    }

    public function test_product_metric_policy_enforces_ownership_and_admin_permissions(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $sharedMetric = ProductMetric::factory()->create(['user_id' => null]);
        $userAMetric = ProductMetric::factory()->create(['user_id' => $userA->id]);

        $this->assertTrue(Gate::forUser($userA)->allows('view', $userAMetric));
        $this->assertFalse(Gate::forUser($userB)->allows('view', $userAMetric));
        $this->assertFalse(Gate::forUser($userB)->allows('view', $sharedMetric));
        $this->assertFalse(Gate::forUser($userA)->allows('viewAny', ProductMetric::class));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $userAMetric));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $userAMetric));

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', ProductMetric::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $userAMetric));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $sharedMetric));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $userAMetric));
    }

    public function test_product_analyze_without_selling_price_never_fabricates_markup_or_profit_in_api_or_db(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 100.0000,
            'original_price' => 130.0000,
            'rating' => 4.6,
            'reviews_count' => 150,
            'is_available' => true,
        ]);
        $canonicalId = $product->canonical_product_id;

        Sanctum::actingAs($user);

        // Case C + Case A: POST without selling_price ({})
        $missingRes = $this->postJson("/api/v1/products/{$canonicalId}/analyze", []);
        $missingRes->assertStatus(201);

        $rawContent = $missingRes->getContent();
        $this->assertStringNotContainsString('150.0000', $rawContent);
        $this->assertStringNotContainsString('50.0000', $rawContent);
        $this->assertStringNotContainsString('33.3333', $rawContent);
        $this->assertStringNotContainsString('130.0000', $rawContent);

        $missingRes->assertJson([
            'success' => true,
            'data' => [
                'purchase_price' => '100.0000',
                'selling_price' => null,
                'estimated_profit' => null,
                'profit_margin' => null,
                'return_on_investment' => null,
                'calculation_notes' => [
                    'is_profitable' => null,
                    'status' => 'DATA_UNAVAILABLE',
                    'missing_critical_data' => ['selling_price'],
                ],
            ],
        ]);

        $missingMetricId = $missingRes->json('data.id');
        $this->assertDatabaseHas('product_metrics', [
            'id' => $missingMetricId,
            'canonical_product_id' => $canonicalId,
            'purchase_price' => '100.0000',
            'selling_price' => null,
            'estimated_profit' => null,
            'profit_margin' => null,
            'return_on_investment' => null,
        ]);

        // Product details GET endpoint also reflects null rather than fabricated metrics
        $detailsRes = $this->getJson("/api/v1/products/{$canonicalId}");
        $detailsRes->assertStatus(200);
        $this->assertNull($detailsRes->json('data.latest_metric.selling_price'));
        $this->assertNull($detailsRes->json('data.latest_metric.estimated_profit'));
        $this->assertNull($detailsRes->json('data.latest_metric.profit_margin'));
        $this->assertNull($detailsRes->json('data.latest_metric.return_on_investment'));
        $this->assertNull($detailsRes->json('data.latest_metric.calculation_notes.is_profitable'));
        $this->assertSame('DATA_UNAVAILABLE', $detailsRes->json('data.latest_metric.calculation_notes.status'));

        // Case B: Explicit selling_price continues computing normally
        $explicitRes = $this->postJson("/api/v1/products/{$canonicalId}/analyze", [
            'selling_price' => 150.00,
        ]);
        $explicitRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'purchase_price' => '100.0000',
                    'selling_price' => '150.0000',
                    'estimated_profit' => '50.0000',
                    'profit_margin' => '33.3333',
                    'return_on_investment' => '50.0000',
                    'calculation_notes' => [
                        'is_profitable' => true,
                    ],
                ],
            ]);
    }
}

