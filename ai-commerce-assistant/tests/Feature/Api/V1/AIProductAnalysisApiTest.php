<?php

namespace Tests\Feature\Api\V1;

use App\Http\Resources\Api\V1\AI\AIAnalysisResource;
use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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

    public function test_owner_can_view_own_historical_ai_analysis(): void
    {
        Sanctum::actingAs($this->user);

        $createRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 320.00,
            'selling_price' => 550.00,
            'shipping_cost' => 15.00,
        ]);

        $createRes->assertStatus(200);
        $analysisId = $createRes->json('data.id');

        $this->getJson("/api/v1/ai/analyses/{$analysisId}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Historical AI analysis retrieved successfully.',
                'data' => [
                    'id' => $analysisId,
                    'canonical_product_id' => $this->product->id,
                ],
            ]);
    }

    public function test_user_b_cannot_access_user_a_ai_analysis_idor_is_blocked(): void
    {
        // User A creates a private analysis containing custom supplier costs
        Sanctum::actingAs($this->user);
        $createRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 123.45,
            'selling_price' => 599.99,
            'shipping_cost' => 12.00,
        ]);
        $createRes->assertStatus(200);
        $userAAnalysisId = $createRes->json('data.id');

        // User B authenticates and attempts to access User A's analysis ID
        $userB = User::factory()->create();
        Sanctum::actingAs($userB);

        $forbiddenRes = $this->getJson("/api/v1/ai/analyses/{$userAAnalysisId}");

        $forbiddenRes->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Access denied',
                'errors' => [],
            ]);

        $content = $forbiddenRes->getContent();
        $this->assertStringNotContainsString('Garmin Fenix 7', $content);
        $this->assertStringNotContainsString('123.45', $content);
        $this->assertStringNotContainsString((string) $this->user->email, $content);
    }

    public function test_changing_id_across_multiple_records_cannot_bypass_authorization(): void
    {
        $userB = User::factory()->create();

        // User A creates two analyses (product + listing)
        Sanctum::actingAs($this->user);
        $userAProductRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 210.00,
        ]);
        $userAAnalysisId1 = $userAProductRes->json('data.id');

        $userAListingRes = $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [
            'purchase_price' => 220.00,
        ]);
        $userAAnalysisId2 = $userAListingRes->json('data.id');

        // User B creates their own analysis, then probes own ID and User A's IDs
        Sanctum::actingAs($userB);
        $userBRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 390.00,
        ]);
        $userBAnalysisId = $userBRes->json('data.id');

        $this->getJson("/api/v1/ai/analyses/{$userBAnalysisId}")->assertStatus(200);
        $this->getJson("/api/v1/ai/analyses/{$userAAnalysisId1}")->assertStatus(403);
        $this->getJson("/api/v1/ai/analyses/{$userAAnalysisId2}")->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_retrieve_historical_ai_analysis(): void
    {
        Sanctum::actingAs($this->user);
        $createRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", []);
        $analysisId = $createRes->json('data.id');

        // Reset auth state to simulate anonymous client
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/v1/ai/analyses/{$analysisId}")
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);

        $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [])
            ->assertStatus(401);
    }

    public function test_cannot_spoof_user_id_when_creating_ai_analysis(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        Sanctum::actingAs($attacker);

        $res = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'user_id' => $victim->id,
        ]);

        $res->assertStatus(200);
        $analysisId = $res->json('data.id');

        $this->assertDatabaseHas('ai_analyses', [
            'id' => $analysisId,
            'user_id' => $attacker->id,
        ]);
        $this->assertDatabaseMissing('ai_analyses', [
            'id' => $analysisId,
            'user_id' => $victim->id,
        ]);
    }

    public function test_boundary_cases_for_ai_analysis_authorization_and_lookup(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Legacy/unowned record (user_id = null) must be denied for regular users
        $unownedAnalysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => null,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'WATCH',
            'opportunity_score' => 55.00,
            'risk_score' => 45.00,
            'confidence' => 0.60,
            'deterministic_metrics' => ['estimated_profit' => '99.99'],
            'context_snapshot' => ['secret_legacy_note' => 'internal-only'],
            'ai_response' => ['recommendation' => ['decision' => 'WATCH', 'confidence' => 0.60, 'summary' => 'Legacy']],
        ]);

        $unownedRes = $this->getJson("/api/v1/ai/analyses/{$unownedAnalysis->id}");
        $unownedRes->assertStatus(403)
            ->assertExactJson([
                'success' => false,
                'message' => 'Access denied',
                'errors' => [],
            ]);
        $this->assertStringNotContainsString('internal-only', $unownedRes->getContent());

        // 2. Non-existent numeric ID returns safe 404 without data leakage
        $this->getJson('/api/v1/ai/analyses/999999')
            ->assertStatus(404)
            ->assertExactJson([
                'success' => false,
                'message' => 'AI Analysis not found.',
                'errors' => [],
            ]);

        // 3. Malformed / unexpected IDs return clean 404 (no 500 or input reflection)
        foreach (['abc', '-1', '0', '1,2'] as $unexpectedId) {
            $badRes = $this->getJson("/api/v1/ai/analyses/{$unexpectedId}");
            $badRes->assertStatus(404)
                ->assertExactJson([
                    'success' => false,
                    'message' => 'AI Analysis not found.',
                    'errors' => [],
                ]);
        }

        // 4. Authorized admin can access an analysis record
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $this->getJson("/api/v1/ai/analyses/{$unownedAnalysis->id}")
            ->assertStatus(200);
    }

    public function test_regular_user_cannot_override_mock_provider_in_testing_and_mock_is_blocked_in_production(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-live-cost-abuse-key',
        ]);

        Http::fake();

        // 1. Regular user in testing environment sending driver=openai -> forced to mock
        Sanctum::actingAs($this->user);

        $productRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'driver' => 'openai',
        ]);
        $productRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'mock',
                ],
            ]);

        $listingRes = $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [
            'purchase_price' => 300.00,
            'driver' => 'openai',
        ]);
        $listingRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'mock',
                ],
            ]);

        $countBeforeProd = AIAnalysis::count();

        // 2. Simulate production environment: AI_PROVIDER=mock MUST fail closed (neither MockAIProvider nor override runs)
        $this->app->detectEnvironment(fn () => 'production');

        $prodUserRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'driver' => 'openai',
        ]);
        $prodUserRes->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $prodAdminRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'driver' => 'openai',
        ]);
        $prodAdminRes->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame($countBeforeProd, AIAnalysis::count());
        Http::assertNothingSent();
    }

    public function test_production_real_ai_provider_without_credentials_fails_closed_without_saving_mock_data(): void
    {
        config([
            'ai.default' => 'openai',
            'ai.providers.openai.api_key' => '',
        ]);

        Http::fake();
        $this->app->detectEnvironment(fn () => 'production');
        Sanctum::actingAs($this->user);

        $res = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'selling_price' => 500.00,
            'save' => true,
        ]);

        $res->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('missing required production credentials', $res->json('message'));
        $this->assertSame(0, AIAnalysis::count());
        Http::assertNothingSent();
    }

    public function test_production_ignores_mock_driver_override_and_uses_configured_openai_provider(): void
    {
        config([
            'ai.default' => 'openai',
            'ai.providers.openai.api_key' => 'sk-prod-valid-openai-key',
        ]);

        $sampleStructured = [
            'recommendation' => [
                'decision' => 'BUY',
                'confidence' => 0.85,
                'summary' => 'Production OpenAI response.',
            ],
            'opportunity' => ['score' => 80, 'level' => 'HIGH', 'justification' => 'OK'],
            'profitability' => ['profit' => '154.9900', 'margin' => '25.83%', 'assessment' => 'Good'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Low'],
            'risk' => ['level' => 'LOW', 'score' => 25, 'factors' => ['Normal']],
            'reasons' => ['Healthy margin'],
            'warnings' => ['Pending verified shipping'],
            'action_plan' => ['Proceed with test batch'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-prod-test',
                'model' => 'gpt-4o-mini',
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => json_encode($sampleStructured),
                    ],
                ]],
                'usage' => ['total_tokens' => 140],
            ], 200),
        ]);

        $this->app->detectEnvironment(fn () => 'production');
        Sanctum::actingAs($this->user);

        $res = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 380.00,
            'selling_price' => 599.99,
            'shipping_cost' => 20.00,
            'estimated_fees' => 45.00,
            'driver' => 'mock',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'openai',
                ],
            ]);

        $this->assertDatabaseHas('ai_analyses', [
            'id' => $res->json('data.id'),
            'provider' => 'openai',
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com/v1/chat/completions'));
    }

    public function test_admin_can_override_ai_driver_only_in_testing_environment(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-admin-override-key',
        ]);

        $sampleStructured = [
            'recommendation' => [
                'decision' => 'BUY',
                'confidence' => 0.85,
                'summary' => 'Controlled admin test.',
            ],
            'opportunity' => ['score' => 80, 'level' => 'HIGH', 'justification' => 'OK'],
            'profitability' => ['profit' => '50.00', 'margin' => '30%', 'assessment' => 'Good'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Low'],
            'risk' => ['level' => 'LOW', 'score' => 25, 'factors' => ['None']],
            'reasons' => ['Margin'],
            'warnings' => ['Pending shipping'],
            'action_plan' => ['Verify supplier'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-admin-override',
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode($sampleStructured),
                        ],
                    ],
                ],
                'usage' => ['total_tokens' => 150],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $res = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'driver' => 'openai',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'openai',
                ],
            ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com/v1/chat/completions'));
    }

    public function test_duration_ms_contract_case_a_decimal_value_returns_json_number_on_get(): void
    {
        Sanctum::actingAs($this->user);

        $analysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'WATCH',
            'opportunity_score' => 55.00,
            'risk_score' => 45.00,
            'confidence' => 0.60,
            'deterministic_metrics' => ['estimated_profit' => '99.99'],
            'context_snapshot' => [],
            'ai_response' => ['recommendation' => ['decision' => 'WATCH', 'confidence' => 0.60, 'summary' => 'Decimal duration check']],
            'duration_ms' => 14.56,
            'tokens_used' => 120,
        ]);

        $response = $this->getJson("/api/v1/ai/analyses/{$analysis->id}");

        $response->assertStatus(200);
        $duration = $response->json('data.duration_ms');
        $this->assertIsFloat($duration);
        $this->assertSame(14.56, $duration);
        $this->assertStringContainsString('"duration_ms":14.56', $response->getContent());
        $this->assertStringNotContainsString('"duration_ms":"14.56"', $response->getContent());
    }

    public function test_duration_ms_contract_case_b_null_remains_null_never_zero(): void
    {
        Sanctum::actingAs($this->user);

        $analysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'WATCH',
            'opportunity_score' => 55.00,
            'risk_score' => 45.00,
            'confidence' => 0.60,
            'deterministic_metrics' => ['estimated_profit' => '99.99'],
            'context_snapshot' => [],
            'ai_response' => ['recommendation' => ['decision' => 'WATCH', 'confidence' => 0.60, 'summary' => 'Null duration check']],
            'duration_ms' => null,
            'tokens_used' => 120,
        ]);

        $response = $this->getJson("/api/v1/ai/analyses/{$analysis->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('duration_ms', $response->json('data'));
        $this->assertNull($response->json('data.duration_ms'));
        $this->assertStringContainsString('"duration_ms":null', $response->getContent());

        // Ensure AIAnalysisResource array payload also preserves null (without PHP warnings) and explicit 0.0
        $nullResource = (new AIAnalysisResource(['id' => 1, 'duration_ms' => null]))->toArray(Request::create('/'));
        $this->assertArrayHasKey('duration_ms', $nullResource);
        $this->assertNull($nullResource['duration_ms']);

        $zeroResource = (new AIAnalysisResource(['id' => 2, 'duration_ms' => 0.0]))->toArray(Request::create('/'));
        $this->assertIsFloat($zeroResource['duration_ms']);
        $this->assertSame(0.0, $zeroResource['duration_ms']);
    }

    public function test_duration_ms_contract_case_c_post_and_get_return_identical_numeric_type_and_value(): void
    {
        Sanctum::actingAs($this->user);

        $postProductRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 300.00,
            'selling_price' => 500.00,
            'shipping_cost' => 15.00,
            'save' => true,
        ]);

        $postProductRes->assertStatus(200);
        $postDuration = $postProductRes->json('data.duration_ms');
        $this->assertTrue(
            is_float($postDuration) || is_int($postDuration),
            'POST product analysis duration_ms must serialize as JSON number, got: ' . gettype($postDuration)
        );
        $this->assertGreaterThanOrEqual(0, $postDuration);

        $analysisId = $postProductRes->json('data.id');
        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");

        $getRes->assertStatus(200);
        $getDuration = $getRes->json('data.duration_ms');
        $this->assertTrue(
            is_float($getDuration) || is_int($getDuration),
            'GET analysis duration_ms must serialize as JSON number, got: ' . gettype($getDuration)
        );
        $this->assertSame($postDuration, $getDuration);

        // Listing POST + GET consistency as well
        $postListingRes = $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [
            'purchase_price' => 300.00,
            'save' => true,
        ]);

        $postListingRes->assertStatus(200);
        $postListingDuration = $postListingRes->json('data.duration_ms');
        $this->assertTrue(is_float($postListingDuration) || is_int($postListingDuration));

        $listingAnalysisId = $postListingRes->json('data.id');
        $getListingRes = $this->getJson("/api/v1/ai/analyses/{$listingAnalysisId}");

        $getListingRes->assertStatus(200);
        $this->assertSame($postListingDuration, $getListingRes->json('data.duration_ms'));

        // Resource array vs model consistency for exact fractional decimal 14.56
        $postStyleResource = (new AIAnalysisResource(['id' => 99, 'duration_ms' => 14.56]))->toArray(Request::create('/'));
        $this->assertIsFloat($postStyleResource['duration_ms']);
        $this->assertSame(14.56, $postStyleResource['duration_ms']);
    }

    public function test_numeric_scores_contract_tests_1_and_2_post_and_get_return_json_numbers_never_strings(): void
    {
        Sanctum::actingAs($this->user);

        $postRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 380.00,
            'selling_price' => 599.99,
            'shipping_cost' => 20.00,
            'estimated_fees' => 45.00,
            'save' => true,
        ]);

        $postRes->assertStatus(200);
        foreach (['confidence', 'opportunity_score', 'risk_score'] as $field) {
            $postValue = $postRes->json("data.{$field}");
            $this->assertTrue(
                is_float($postValue) || is_int($postValue),
                "POST data.{$field} must be a JSON number, got " . gettype($postValue)
            );
        }

        $analysisId = $postRes->json('data.id');
        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");

        $getRes->assertStatus(200);
        foreach (['confidence', 'opportunity_score', 'risk_score'] as $field) {
            $postValue = $postRes->json("data.{$field}");
            $getValue = $getRes->json("data.{$field}");
            $this->assertTrue(
                is_float($getValue) || is_int($getValue),
                "GET data.{$field} must be a JSON number, got " . gettype($getValue)
            );
            $this->assertSame($postValue, $getValue);
        }
    }

    public function test_numeric_scores_contract_test_3_confidence_0_875_precision_preserved_across_post_db_and_get(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-precision-key',
        ]);

        $this->assertTrue(Schema::hasColumn('ai_analyses', 'confidence'));

        $aiPayload = [
            'recommendation' => [
                'decision' => 'BUY',
                'confidence' => 0.875,
                'summary' => 'Precision test.',
            ],
            'opportunity' => ['score' => 82.5, 'level' => 'HIGH', 'justification' => 'OK'],
            'profitability' => ['profit' => '154.9900', 'margin' => '25.83%', 'assessment' => 'Profitable'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Low'],
            'risk' => ['level' => 'LOW', 'score' => 24.5, 'factors' => ['Normal variance']],
            'reasons' => ['Healthy spread'],
            'warnings' => [],
            'action_plan' => ['Test batch'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-precision-test',
                'model' => 'gpt-4o-mini',
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => json_encode($aiPayload)],
                ]],
                'usage' => ['total_tokens' => 111],
            ], 200),
        ]);

        $this->listing->update([
            'raw_payload' => array_merge($this->listing->raw_payload ?? [], [
                'shipping_cost' => 20.00,
                'shipping_verified' => true,
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $postRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 380.00,
            'selling_price' => 599.99,
            'shipping_cost' => 20.00,
            'estimated_fees' => 45.00,
            'driver' => 'openai',
            'save' => true,
        ]);

        $postRes->assertStatus(200);
        $this->assertSame(0.875, $postRes->json('data.confidence'));
        $this->assertStringContainsString('"confidence":0.875', $postRes->getContent());

        $analysisId = $postRes->json('data.id');
        $persisted = AIAnalysis::findOrFail($analysisId);
        $this->assertSame(0.875, $persisted->confidence);

        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");
        $getRes->assertStatus(200);
        $this->assertSame(0.875, $getRes->json('data.confidence'));
        $this->assertStringContainsString('"confidence":0.875', $getRes->getContent());
        $this->assertStringNotContainsString('"confidence":0.88', $getRes->getContent());
        $this->assertStringNotContainsString('"confidence":"0.88"', $getRes->getContent());
    }

    public function test_numeric_scores_contract_test_4_null_scores_remain_null_and_zero_remains_zero(): void
    {
        Sanctum::actingAs($this->user);

        $nullAnalysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'INSUFFICIENT_DATA',
            'opportunity_score' => null,
            'risk_score' => null,
            'confidence' => null,
            'deterministic_metrics' => [],
            'context_snapshot' => [],
            'ai_response' => ['recommendation' => ['decision' => 'INSUFFICIENT_DATA', 'summary' => 'Missing scores']],
            'duration_ms' => null,
            'tokens_used' => 0,
        ]);

        $nullRes = $this->getJson("/api/v1/ai/analyses/{$nullAnalysis->id}");
        $nullRes->assertStatus(200);
        $this->assertNull($nullRes->json('data.confidence'));
        $this->assertNull($nullRes->json('data.opportunity_score'));
        $this->assertNull($nullRes->json('data.risk_score'));
        $this->assertStringContainsString('"confidence":null', $nullRes->getContent());
        $this->assertStringContainsString('"opportunity_score":null', $nullRes->getContent());
        $this->assertStringContainsString('"risk_score":null', $nullRes->getContent());

        $zeroAnalysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'WATCH',
            'opportunity_score' => 0.00,
            'risk_score' => 0.00,
            'confidence' => 0.00,
            'deterministic_metrics' => [],
            'context_snapshot' => [],
            'ai_response' => ['recommendation' => ['decision' => 'WATCH', 'confidence' => 0.0, 'summary' => 'Zero scores']],
            'duration_ms' => 0.0,
            'tokens_used' => 0,
        ]);

        $zeroRes = $this->getJson("/api/v1/ai/analyses/{$zeroAnalysis->id}");
        $zeroRes->assertStatus(200);
        $this->assertEquals(0.0, $zeroRes->json('data.confidence'));
        $this->assertNotNull($zeroRes->json('data.confidence'));
        $this->assertEquals(0.0, $zeroRes->json('data.opportunity_score'));
        $this->assertNotNull($zeroRes->json('data.opportunity_score'));
        $this->assertEquals(0.0, $zeroRes->json('data.risk_score'));
        $this->assertNotNull($zeroRes->json('data.risk_score'));
    }

    public function test_numeric_scores_contract_tests_5_and_6_deterministic_opportunity_and_risk_supremacy_end_to_end(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-supremacy-key',
        ]);

        $product = CanonicalProduct::create([
            'name' => 'Low Margin High Risk Item',
            'slug' => 'low-margin-high-risk-item',
            'base_currency' => 'USD',
        ]);
        $source = ProductSource::factory()->create(['code' => 'amazon']);
        ProductListing::create([
            'canonical_product_id' => $product->id,
            'source_id' => $source->id,
            'external_id' => 'AMZ-RISK-1',
            'title' => 'Low Margin High Risk Item',
            'price' => 110.00,
            'currency' => 'USD',
            'rating' => 3.2,
            'reviews_count' => 5,
            'is_available' => true,
            'url' => 'https://amazon.com/dp/AMZRISK1',
        ]);

        // Calculate exact deterministic expectation from Laravel's context builder
        $context = app(\App\Services\AI\Context\AIContextBuilder::class)->build($product, [
            'purchase_price' => 101.00,
            'selling_price' => 110.00,
            'shipping_cost' => 2.00,
        ]);
        $expectedOpp = round((float) $context['deterministic_heuristics']['opportunity_score']['value'], 2);
        $expectedRisk = round((float) $context['deterministic_heuristics']['risk_score']['value'], 2);
        $this->assertGreaterThanOrEqual(60.0, $expectedRisk);

        // AI attempts to hallucinate opposite extreme scores (opp=99, risk=1) and BUY
        $aiHallucination = [
            'recommendation' => ['decision' => 'BUY', 'confidence' => 0.92, 'summary' => 'Hallucinated buy'],
            'opportunity' => ['score' => 99, 'level' => 'HIGH', 'justification' => 'Fake 99'],
            'profitability' => ['profit' => '500.00', 'margin' => '80%', 'assessment' => 'Fake'],
            'competition' => ['level' => 'LOW', 'score' => 5, 'summary' => 'Fake'],
            'risk' => ['level' => 'LOW', 'score' => 1, 'factors' => ['None']],
            'reasons' => ['Fake'],
            'warnings' => [],
            'action_plan' => ['Wait'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-supremacy',
                'model' => 'gpt-4o-mini',
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => json_encode($aiHallucination)],
                ]],
                'usage' => ['total_tokens' => 120],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $postRes = $this->postJson("/api/v1/ai/products/{$product->id}/analyze", [
            'purchase_price' => 101.00,
            'selling_price' => 110.00,
            'shipping_cost' => 2.00,
            'driver' => 'openai',
            'save' => true,
        ]);

        $postRes->assertStatus(200);
        $this->assertEquals($expectedOpp, $postRes->json('data.opportunity_score'));
        $this->assertEquals($expectedOpp, $postRes->json('data.analysis.opportunity.score'));
        $this->assertEquals($expectedRisk, $postRes->json('data.risk_score'));
        $this->assertEquals($expectedRisk, $postRes->json('data.analysis.risk.score'));
        $this->assertSame('WATCH', $postRes->json('data.decision'));

        $analysisId = $postRes->json('data.id');
        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");
        $getRes->assertStatus(200);
        $this->assertEquals($expectedOpp, $getRes->json('data.opportunity_score'));
        $this->assertEquals($expectedRisk, $getRes->json('data.risk_score'));
    }

    public function test_numeric_scores_contract_test_7_confidence_capped_under_insufficient_data(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-insufficient-key',
        ]);

        $emptyProduct = CanonicalProduct::create([
            'name' => 'No Offers Product',
            'slug' => 'no-offers-product',
            'base_currency' => 'USD',
        ]);

        $aiOverconfident = [
            'recommendation' => ['decision' => 'BUY', 'confidence' => 0.96, 'summary' => 'Unwarranted certainty'],
            'opportunity' => ['score' => 88, 'level' => 'HIGH', 'justification' => 'Overconfident'],
            'profitability' => ['profit' => '50.00', 'margin' => '25%', 'assessment' => 'Unverified'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Unknown'],
            'risk' => ['level' => 'LOW', 'score' => 20, 'factors' => ['Missing market offers']],
            'reasons' => ['Theoretical spread'],
            'warnings' => [],
            'action_plan' => ['Find market listings'],
            'data_truth_summary' => ['observed_data_available' => false, 'missing_critical_data' => ['active_marketplace_offers']],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-insufficient',
                'model' => 'gpt-4o-mini',
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => json_encode($aiOverconfident)],
                ]],
                'usage' => ['total_tokens' => 105],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $postRes = $this->postJson("/api/v1/ai/products/{$emptyProduct->id}/analyze", [
            'purchase_price' => 50.00,
            'selling_price' => 100.00,
            'shipping_cost' => 10.00,
            'driver' => 'openai',
            'save' => true,
        ]);

        $postRes->assertStatus(200);
        $this->assertSame('INSUFFICIENT_DATA', $postRes->json('data.decision'));
        $this->assertSame(0.35, $postRes->json('data.confidence'));
        $this->assertSame(0.35, $postRes->json('data.analysis.recommendation.confidence'));

        $analysisId = $postRes->json('data.id');
        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");
        $getRes->assertStatus(200);
        $this->assertSame(0.35, $getRes->json('data.confidence'));
    }

    public function test_percentage_contract_cases_a_b_d_numeric_json_and_post_get_parity(): void
    {
        Sanctum::actingAs($this->user);

        // purchase=120, selling=220, shipping=15, fees=22, other=5
        // total=162, profit=58, margin=26.3636, roi=35.8024
        $postRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 120.00,
            'selling_price' => 220.00,
            'shipping_cost' => 15.00,
            'estimated_fees' => 22.00,
            'other_costs' => 5.00,
            'save' => true,
        ]);

        $postRes->assertStatus(200);

        $postPayload = json_decode($postRes->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $postMargin = $postPayload['data']['deterministic_metrics']['profit_margin_percent']['value'] ?? null;
        $postRoi = $postPayload['data']['deterministic_metrics']['return_on_investment_percent']['value'] ?? null;
        $postAnalysisMargin = $postPayload['data']['analysis']['profitability']['margin'] ?? null;

        $this->assertIsFloat($postMargin, 'POST profit_margin_percent.value must be JSON float');
        $this->assertSame(26.3636, $postMargin);
        $this->assertIsFloat($postRoi, 'POST return_on_investment_percent.value must be JSON float');
        $this->assertSame(35.8024, $postRoi);
        $this->assertIsFloat($postAnalysisMargin, 'POST analysis.profitability.margin must be JSON float');
        $this->assertSame(26.3636, $postAnalysisMargin);

        $rawPost = $postRes->getContent();
        $this->assertStringContainsString('"value":26.3636', $rawPost);
        $this->assertStringContainsString('"value":35.8024', $rawPost);
        $this->assertStringContainsString('"margin":26.3636', $rawPost);
        $this->assertStringNotContainsString('"value":"26.3636%"', $rawPost);
        $this->assertStringNotContainsString('"value":"26.3636"', $rawPost);
        $this->assertStringNotContainsString('"value":"35.8024%"', $rawPost);
        $this->assertStringNotContainsString('"value":"35.8024"', $rawPost);
        $this->assertStringNotContainsString('"margin":"26.3636%"', $rawPost);
        $this->assertStringNotContainsString('"margin":"26.3636"', $rawPost);

        // GET parity on persisted analysis
        $analysisId = $postRes->json('data.id');
        $getRes = $this->getJson("/api/v1/ai/analyses/{$analysisId}");
        $getRes->assertStatus(200);

        $getPayload = json_decode($getRes->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $getMargin = $getPayload['data']['deterministic_metrics']['profit_margin_percent']['value'] ?? null;
        $getRoi = $getPayload['data']['deterministic_metrics']['return_on_investment_percent']['value'] ?? null;
        $getAnalysisMargin = $getPayload['data']['analysis']['profitability']['margin'] ?? null;

        $this->assertIsFloat($getMargin, 'GET profit_margin_percent.value must be JSON float');
        $this->assertSame($postMargin, $getMargin);
        $this->assertIsFloat($getRoi, 'GET return_on_investment_percent.value must be JSON float');
        $this->assertSame($postRoi, $getRoi);
        $this->assertIsFloat($getAnalysisMargin, 'GET analysis.profitability.margin must be JSON float');
        $this->assertSame($postAnalysisMargin, $getAnalysisMargin);

        $rawGet = $getRes->getContent();
        $this->assertStringNotContainsString('"value":"26.3636%"', $rawGet);
        $this->assertStringNotContainsString('"value":"26.3636"', $rawGet);
        $this->assertStringNotContainsString('"value":"35.8024%"', $rawGet);
        $this->assertStringNotContainsString('"margin":"26.3636%"', $rawGet);
    }

    public function test_percentage_contract_case_c_ai_percent_string_is_never_leaked_to_canonical_response(): void
    {
        config([
            'ai.default' => 'mock',
            'ai.providers.openai.api_key' => 'sk-test-percent-normalization',
        ]);

        // OpenAI returns legacy "%" formatted margin "99.00%"
        $aiPayload = [
            'recommendation' => ['decision' => 'BUY', 'confidence' => 0.85, 'summary' => 'Percent test'],
            'opportunity' => ['score' => 80, 'level' => 'HIGH', 'justification' => 'OK'],
            'profitability' => ['profit' => '58.0000', 'margin' => '26.36%', 'assessment' => 'Profitable'],
            'competition' => ['level' => 'LOW', 'score' => 20, 'summary' => 'Low'],
            'risk' => ['level' => 'LOW', 'score' => 20, 'factors' => ['Minimal']],
            'reasons' => ['Margin'],
            'warnings' => [],
            'action_plan' => ['Proceed'],
            'data_truth_summary' => ['observed_data_available' => true, 'missing_critical_data' => []],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-percent-norm',
                'model' => 'gpt-4o-mini',
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => json_encode($aiPayload)],
                ]],
                'usage' => ['total_tokens' => 110],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $postRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 120.00,
            'selling_price' => 220.00,
            'shipping_cost' => 15.00,
            'estimated_fees' => 22.00,
            'other_costs' => 5.00,
            'driver' => 'openai',
            'save' => true,
        ]);

        $postRes->assertStatus(200);
        // Deterministic value (26.3636) overrides AI "26.36%" and emits pure numeric JSON
        $margin = $postRes->json('data.analysis.profitability.margin');
        $this->assertIsFloat($margin);
        $this->assertSame(26.3636, $margin);
        $this->assertStringNotContainsString('"margin":"26.36%"', $postRes->getContent());
        $this->assertStringNotContainsString('"margin":"26.3636%"', $postRes->getContent());
    }

    public function test_percentage_contract_case_e_null_and_legacy_percent_db_rows_normalize_cleanly(): void
    {
        Sanctum::actingAs($this->user);

        // Null percentages remain null (never 0 or "0%")
        $nullAnalysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'INSUFFICIENT_DATA',
            'opportunity_score' => null,
            'risk_score' => null,
            'confidence' => null,
            'deterministic_metrics' => [
                'profit_margin_percent' => null,
                'return_on_investment_percent' => ['value' => null, 'truth' => 'CALCULATED'],
            ],
            'context_snapshot' => [],
            'ai_response' => [
                'recommendation' => ['decision' => 'INSUFFICIENT_DATA', 'summary' => 'Missing financials'],
                'profitability' => ['profit' => '0.0000', 'margin' => null, 'assessment' => 'Unavailable'],
            ],
            'duration_ms' => null,
            'tokens_used' => 0,
        ]);

        $nullRes = $this->getJson("/api/v1/ai/analyses/{$nullAnalysis->id}");
        $nullRes->assertStatus(200);
        $this->assertNull($nullRes->json('data.deterministic_metrics.profit_margin_percent'));
        $this->assertNull($nullRes->json('data.deterministic_metrics.return_on_investment_percent.value'));
        $this->assertNull($nullRes->json('data.analysis.profitability.margin'));
        $this->assertStringContainsString('"profit_margin_percent":null', $nullRes->getContent());
        $this->assertStringNotContainsString('"0%"', $nullRes->getContent());

        // Legacy persisted DB row containing "%" strings normalizes to pure JSON numbers on GET
        $legacyAnalysis = AIAnalysis::create([
            'canonical_product_id' => $this->product->id,
            'product_listing_id' => $this->listing->id,
            'user_id' => $this->user->id,
            'provider' => 'mock',
            'model' => 'mock-v1',
            'prompt_version' => 'v1',
            'decision' => 'WATCH',
            'opportunity_score' => 60,
            'risk_score' => 30,
            'confidence' => 0.70,
            'deterministic_metrics' => [
                'profit_margin_percent' => ['value' => '26.3636%', 'truth' => 'CALCULATED'],
                'return_on_investment_percent' => ['value' => '35.8024%', 'truth' => 'CALCULATED'],
            ],
            'context_snapshot' => [],
            'ai_response' => [
                'recommendation' => ['decision' => 'WATCH', 'confidence' => 0.70, 'summary' => 'Legacy record'],
                'profitability' => ['profit' => '58.0000', 'margin' => '26.3636%', 'assessment' => 'Profitable'],
            ],
            'duration_ms' => 12.0,
            'tokens_used' => 100,
        ]);

        $legacyRes = $this->getJson("/api/v1/ai/analyses/{$legacyAnalysis->id}");
        $legacyRes->assertStatus(200);
        $this->assertSame(26.3636, $legacyRes->json('data.deterministic_metrics.profit_margin_percent.value'));
        $this->assertSame(35.8024, $legacyRes->json('data.deterministic_metrics.return_on_investment_percent.value'));
        $this->assertSame(26.3636, $legacyRes->json('data.analysis.profitability.margin'));
        $this->assertStringNotContainsString('%', json_encode($legacyRes->json('data.deterministic_metrics')));
        $this->assertStringNotContainsString('%', (string) json_encode($legacyRes->json('data.analysis.profitability.margin')));
    }

    public function test_scope3_missing_selling_price_never_fabricates_markup_on_product_or_listing_and_preserves_post_get_parity(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Listing analysis with empty financialInputs (purchase price observed = 599.99, selling_price omitted)
        $listingPostRes = $this->postJson("/api/v1/ai/listings/{$this->listing->id}/analyze", [
            'save' => true,
        ]);
        $listingPostRes->assertStatus(200);

        $listingRawPost = $listingPostRes->getContent();
        $this->assertStringNotContainsString('869.9855', $listingRawPost);
        $this->assertStringNotContainsString('269.9955', $listingRawPost);
        $this->assertStringNotContainsString('31.0344', $listingRawPost);

        $this->assertSame('INSUFFICIENT_DATA', $listingPostRes->json('data.decision'));
        $this->assertSame(0.35, $listingPostRes->json('data.confidence'));
        $this->assertSame('DATA_UNAVAILABLE', $listingPostRes->json('data.analysis.profitability.profit'));
        $this->assertNull($listingPostRes->json('data.analysis.profitability.margin'));
        $this->assertContains(
            'verified_selling_price',
            $listingPostRes->json('data.analysis.data_truth_summary.missing_critical_data')
        );

        $listingMetrics = $listingPostRes->json('data.deterministic_metrics');
        $this->assertSame('599.9900', $listingMetrics['purchase_price']['value']);
        $this->assertSame('OBSERVED', $listingMetrics['purchase_price']['truth']);
        $this->assertNull($listingMetrics['selling_price']['value']);
        $this->assertNull($listingMetrics['selling_price']['truth']);
        $this->assertSame('DATA_UNAVAILABLE', $listingMetrics['selling_price']['status']);
        $this->assertNull($listingMetrics['estimated_profit']['value']);
        $this->assertNull($listingMetrics['estimated_profit']['truth']);
        $this->assertNull($listingMetrics['profit_margin_percent']['value']);
        $this->assertNull($listingMetrics['profit_margin_percent']['truth']);
        $this->assertNull($listingMetrics['return_on_investment_percent']['value']);
        $this->assertNull($listingMetrics['return_on_investment_percent']['truth']);
        $this->assertNull($listingMetrics['is_profitable']['value']);
        $this->assertNotTrue($listingMetrics['is_profitable']['value']);
        $this->assertNull($listingMetrics['is_profitable']['truth']);

        $listingAnalysisId = $listingPostRes->json('data.id');
        $listingGetRes = $this->getJson("/api/v1/ai/analyses/{$listingAnalysisId}");
        $listingGetRes->assertStatus(200);
        $this->assertSame('INSUFFICIENT_DATA', $listingGetRes->json('data.decision'));
        $this->assertSame(0.35, $listingGetRes->json('data.confidence'));
        $this->assertSame($listingMetrics, $listingGetRes->json('data.deterministic_metrics'));
        $this->assertSame('DATA_UNAVAILABLE', $listingGetRes->json('data.analysis.profitability.profit'));
        $this->assertNull($listingGetRes->json('data.analysis.profitability.margin'));

        // 2. Canonical product analysis with empty financialInputs (purchase price observed = 599.99, selling_price omitted)
        $productPostRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'save' => true,
        ]);
        $productPostRes->assertStatus(200);

        $productRawPost = $productPostRes->getContent();
        $this->assertStringNotContainsString('869.9855', $productRawPost);
        $this->assertStringNotContainsString('269.9955', $productRawPost);
        $this->assertSame('INSUFFICIENT_DATA', $productPostRes->json('data.decision'));
        $this->assertSame(0.35, $productPostRes->json('data.confidence'));

        $productMetrics = $productPostRes->json('data.deterministic_metrics');
        $this->assertNull($productMetrics['selling_price']['value']);
        $this->assertNull($productMetrics['selling_price']['truth']);
        $this->assertSame('DATA_UNAVAILABLE', $productMetrics['selling_price']['status']);
        $this->assertNull($productMetrics['is_profitable']['value']);
        $this->assertNull($productMetrics['profit_margin_percent']['value']);
        $this->assertNull($productMetrics['return_on_investment_percent']['value']);
        $this->assertContains(
            'verified_selling_price',
            $productPostRes->json('data.analysis.data_truth_summary.missing_critical_data')
        );

        $productAnalysisId = $productPostRes->json('data.id');
        $productGetRes = $this->getJson("/api/v1/ai/analyses/{$productAnalysisId}");
        $productGetRes->assertStatus(200);
        $this->assertSame('INSUFFICIENT_DATA', $productGetRes->json('data.decision'));
        $this->assertSame($productMetrics, $productGetRes->json('data.deterministic_metrics'));
        $this->assertSame('DATA_UNAVAILABLE', $productGetRes->json('data.analysis.profitability.profit'));
        $this->assertNull($productGetRes->json('data.analysis.profitability.margin'));

        // 3. Explicit selling_price supplied -> normal CALCULATED financial behavior remains unchanged
        $explicitRes = $this->postJson("/api/v1/ai/products/{$this->product->id}/analyze", [
            'purchase_price' => 380.00,
            'selling_price' => 599.99,
            'shipping_cost' => 20.00,
            'estimated_fees' => 45.00,
            'save' => true,
        ]);
        $explicitRes->assertStatus(200);
        $explicitMetrics = $explicitRes->json('data.deterministic_metrics');
        $this->assertSame('599.9900', $explicitMetrics['selling_price']['value']);
        $this->assertSame('CALCULATED', $explicitMetrics['selling_price']['truth']);
        $this->assertSame('154.9900', $explicitMetrics['estimated_profit']['value']);
        $this->assertTrue($explicitMetrics['is_profitable']['value']);
        $this->assertNotContains(
            'verified_selling_price',
            $explicitRes->json('data.analysis.data_truth_summary.missing_critical_data')
        );
    }
}

