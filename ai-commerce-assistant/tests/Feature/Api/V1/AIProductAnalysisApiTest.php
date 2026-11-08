<?php

namespace Tests\Feature\Api\V1;

use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AIProductAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CanonicalProduct $product;
    protected ProductListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Smart Watches']);
        $source = ProductSource::factory()->create(['code' => 'ebay']);

        $this->product = CanonicalProduct::create([
            'category_id' => $category->id,
            'name' => 'Garmin Fenix 7 Solar Smartwatch',
            'slug' => 'garmin-fenix-7-solar',
            'brand' => 'Garmin',
            'base_currency' => 'USD',
        ]);

        $this->listing = ProductListing::create([
            'canonical_product_id' => $this->product->id,
            'source_id' => $source->id,
            'category_id' => $category->id,
            'external_id' => 'v1|GARMIN-7|0',
            'title' => 'Garmin Fenix 7 Solar Multisport GPS Watch',
            'price' => 599.99,
            'original_price' => 799.99,
            'currency' => 'USD',
            'is_available' => true,
            'raw_payload' => [
                'seller' => [
                    'username' => 'official_sports_dealer',
                    'feedbackPercentage' => 99.8,
                    'feedbackScore' => 24500,
                ],
                'seller_feedback_percentage' => 99.8,
                'seller_feedback_score' => 24500,
            ],
            'url' => 'https://www.ebay.com/itm/garmin7',
        ]);
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", []);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_analyze_canonical_product(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 380.00,
            'selling_price' => 599.99,
            'shipping_cost' => 20.00,
            'estimated_fees' => 45.00,
            'save' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'AI commerce intelligence analysis completed successfully.',
                'data' => [
                    'canonical_product_id' => $this->product->id,
                    'provider' => 'mock',
                    'prompt_version' => 'v1',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'canonical_product_id',
                    'provider',
                    'model',
                    'prompt_version',
                    'decision',
                    'confidence',
                    'opportunity_score',
                    'risk_score',
                    'analysis' => [
                        'recommendation' => ['decision', 'confidence', 'summary'],
                        'opportunity' => ['score', 'level', 'justification'],
                        'profitability' => ['profit', 'margin', 'assessment'],
                        'competition' => ['level', 'score', 'summary'],
                        'risk' => ['level', 'score', 'factors'],
                        'reasons',
                        'warnings',
                        'action_plan',
                        'data_truth_summary',
                    ],
                    'deterministic_metrics',
                    'duration_ms',
                    'created_at',
                ],
            ]);

        // Total cost = 380 + 20 + 45 = 445.0000
        // Profit = 599.99 - 445.00 = 154.9900
        $profitability = $response->json('data.analysis.profitability');
        $this->assertEquals('154.9900', $profitability['profit']);

        // Check persisted record in DB
        $analysisId = $response->json('data.id');
        $this->assertDatabaseHas('ai_analyses', [
            'id' => $analysisId,
            'canonical_product_id' => $this->product->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_authenticated_user_can_analyze_individual_product_listing(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [
            'purchase_price' => 350.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'AI listing intelligence analysis completed successfully.',
                'data' => [
                    'product_listing_id' => $this->listing->id,
                ],
            ]);
    }

    public function test_can_retrieve_historical_ai_analysis(): void
    {
        Sanctum::actingAs($this->user);

        // First generate analysis
        $res = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", []);
        $analysisId = $res->json('data.id');

        // Then retrieve it via GET endpoint
        $showRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");

        $showRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Historical AI analysis retrieved successfully.',
                'data' => [
                    'id' => $analysisId,
                    'canonical_product_id' => $this->product->id,
                ],
            ]);
    }

    public function test_returns_404_when_product_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai/products/999999/analyze', []);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Canonical Product [999999] not found.',
            ]);
    }

    public function test_validates_financial_input_ranges(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => -50.00, // Negative price invalid
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['purchase_price']);
    }

    public function test_security_credentials_and_secrets_are_never_exposed_in_responses(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", []);
        $content = $response->getContent();

        $this->assertStringNotContainsString('api_key', $content);
        $this->assertStringNotContainsString('client_secret', $content);
        $this->assertStringNotContainsString('Authorization:', $content);
        $this->assertStringNotContainsString('Bearer', $content);
    }

    public function test_case_a_missing_purchase_price_and_no_listings_yields_insufficient_data(): void
    {
        Sanctum::actingAs($this->user);

        // Product without any offers and no purchase price supplied
        $emptyProduct = CanonicalProduct::create([
            'name' => 'Completely Sparse Unknown Item',
            'slug' => 'sparse-unknown-item',
            'base_currency' => 'USD',
        ]);

        $response = $this->postJson("/api/v1/ai/products/{$emptyProduct->id}/analyze", []);

        $response->assertStatus(200);
        $this->assertEquals('INSUFFICIENT_DATA', $response->json('data.decision'));
        $this->assertStringContainsString('Zero active marketplace offers', $response->json('data.analysis.recommendation.summary'));
    }

    public function test_case_b_purchase_price_present_but_shipping_unverified_labels_profit_estimated_with_warning(): void
    {
        Sanctum::actingAs($this->user);

        // Shipping cost intentionally omitted
        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'selling_price' => 500.00,
        ]);

        $response->assertStatus(200);
        $warnings = $response->json('data.analysis.warnings');
        $hasEstimatedWarning = collect($warnings)->contains(function ($w) {
            return str_contains($w, 'Net profit is ESTIMATED and pending verified carrier shipping rate');
        });
        $this->assertTrue($hasEstimatedWarning, 'Expected unverified shipping warning not found.');
        $this->assertLessThanOrEqual(0.75, $response->json('data.confidence'));
    }

    public function test_case_c_no_market_offers_cannot_result_in_buy_even_if_theoretical_numbers_positive(): void
    {
        Sanctum::actingAs($this->user);

        $productWithoutOffers = CanonicalProduct::create([
            'name' => 'Hypothetical Product With Zero Market Presence',
            'slug' => 'hypothetical-zero-offers',
            'base_currency' => 'USD',
        ]);

        // Provide attractive theoretical numbers (purchase 50, selling 200)
        $response = $this->postJson("/api/v1/ai/products/{$productWithoutOffers->id}/analyze", [
            'purchase_price' => 50.00,
            'selling_price' => 200.00,
            'shipping_cost' => 10.00,
        ]);

        $response->assertStatus(200);
        // Strict Data Truth: Must be INSUFFICIENT_DATA because zero offers exist in the market
        $this->assertEquals('INSUFFICIENT_DATA', $response->json('data.decision'));
    }

    public function test_financial_supremacy_negative_profit_cannot_result_in_buy(): void
    {
        Sanctum::actingAs($this->user);

        // Unprofitable scenario: purchase 400 + shipping 50 + fees 60 = 510 total cost, selling for 450 -> loss of -60
        $response = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 400.00,
            'selling_price' => 450.00,
            'shipping_cost' => 50.00,
            'estimated_fees' => 60.00,
        ]);

        $response->assertStatus(200);
        // Decision must be AVOID, never BUY
        $this->assertEquals('AVOID', $response->json('data.decision'));
        $this->assertEquals('-60.0000', $response->json('data.analysis.profitability.profit'));
    }
}

