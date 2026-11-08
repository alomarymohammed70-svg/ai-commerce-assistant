<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMetric;
use App\Models\ProductSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
                        'external_id',
                        'title',
                        'price',
                        'currency',
                        'rating',
                        'reviews_count',
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
                    'id' => $product->id,
                    'title' => 'Detailed Test Product',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
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
}

