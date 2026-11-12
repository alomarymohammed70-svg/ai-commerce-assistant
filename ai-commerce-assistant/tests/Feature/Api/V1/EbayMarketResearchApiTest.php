<?php

namespace Tests\Feature\Api\V1;

use App\Contracts\Marketplaces\MarketConnectorInterface;
use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayClientException;
use App\Exceptions\Ebay\EbayIntegrationException;
use App\Exceptions\Ebay\EbayRateLimitException;
use App\Exceptions\Ebay\EbayServerException;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Models\User;
use App\Services\ExternalSources\Connectors\MockEbayConnector;
use App\Services\ExternalSources\Normalizers\EbayProductNormalizer;
use App\Services\MarketResearch\MarketResearchService;
use App\Services\Product\ProductIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EbayMarketResearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_ebay_search_and_item_details_return_401_and_never_write_to_db(): void
    {
        Http::fake();

        $this->assertSame(0, ProductSource::count());
        $this->assertSame(0, ProductListing::count());
        $this->assertSame(0, CanonicalProduct::count());

        $searchRes = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&store=true&driver=production');
        $searchRes->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);

        $itemRes = $this->getJson('/api/v1/market-research/ebay/items/v1%7CEBAY-PRINTER-101%7C0?store=true&driver=production');
        $itemRes->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);

        $this->assertSame(0, ProductSource::count());
        $this->assertSame(0, ProductListing::count());
        $this->assertSame(0, CanonicalProduct::count());
        Http::assertNothingSent();
    }

    public function test_can_search_ebay_marketplace_and_ingest_listings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'eBay market search completed successfully',
                'data' => [
                    'query' => 'wireless printer',
                    'source' => 'ebay',
                    'is_mock' => true,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'query',
                    'source',
                    'is_mock',
                    'cached',
                    'duration_ms',
                    'pagination' => ['total', 'limit', 'offset', 'count'],
                    'items' => [
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
                ],
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, $data['pagination']['count']);

        // Check that listing was persisted in database
        $firstExternalId = $data['items'][0]['external_id'];
        $this->assertDatabaseHas('product_listings', [
            'external_id' => $firstExternalId,
        ]);

        // Check that Canonical Product was associated
        $listing = ProductListing::where('external_id', $firstExternalId)->first();
        $this->assertNotNull($listing->canonical_product_id);
        $this->assertNotNull(CanonicalProduct::find($listing->canonical_product_id));
    }

    public function test_search_ingestion_is_strictly_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        // First search call
        $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');
        $ebaySource = ProductSource::where('code', 'ebay')->first();
        $countAfterFirst = ProductListing::where('source_id', $ebaySource->id)->count();

        // Second search call (fresh=true to re-execute ingestion)
        $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');
        $countAfterSecond = ProductListing::where('source_id', $ebaySource->id)->count();

        // Idempotency: exact same number of listings, ZERO duplicate insertion!
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    public function test_search_results_are_cached_on_subsequent_requests(): void
    {
        Sanctum::actingAs($this->user);

        $res1 = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer');
        $res1->assertStatus(200);
        $this->assertFalse($res1->json('data.cached'));

        // Second call without fresh flag should hit cache
        $res2 = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer');
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('data.cached'));

        // Third call with fresh=true bypasses cache
        $res3 = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');
        $res3->assertStatus(200);
        $this->assertFalse($res3->json('data.cached'));
    }

    public function test_raw_payload_is_suppressed_by_default_and_included_when_requested(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Without include_raw: raw_payload must be omitted
        $resWithoutRaw = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');
        $resWithoutRaw->assertStatus(200);
        $this->assertArrayNotHasKey('raw_payload', $resWithoutRaw->json('data'));
        $this->assertArrayNotHasKey('raw_payload', $resWithoutRaw->json('data.items.0'));

        // 2. With include_raw=true: raw_payload is included
        $resWithRaw = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&include_raw=true&fresh=true');
        $resWithRaw->assertStatus(200);
        $this->assertArrayHasKey('raw_payload', $resWithRaw->json('data'));
        $this->assertArrayHasKey('raw_payload', $resWithRaw->json('data.items.0'));
    }

    public function test_can_fetch_single_ebay_item_details(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market-research/ebay/items/v1|EBAY-PRINTER-101|0');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'eBay item details retrieved successfully',
                'data' => [
                    'external_id' => 'v1|EBAY-PRINTER-101|0',
                    'source' => 'ebay',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'external_id',
                    'source',
                    'is_mock',
                    'cached',
                    'duration_ms',
                    'listing' => [
                        'id',
                        'external_id',
                        'title',
                        'price',
                    ],
                ],
            ]);
    }

    public function test_search_validates_required_parameters(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market-research/ebay/search');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['q']);
    }

    public function test_security_credentials_and_tokens_are_never_exposed_in_responses(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&include_raw=true');

        $responseContent = $response->getContent();

        $this->assertStringNotContainsString('client_secret', $responseContent);
        $this->assertStringNotContainsString('ClientSecret', $responseContent);
        $this->assertStringNotContainsString('access_token', $responseContent);
        $this->assertStringNotContainsString('Authorization:', $responseContent);
    }

    public function test_client_cannot_override_ebay_mock_mode_in_testing_and_mock_is_blocked_in_production(): void
    {
        config([
            'services.ebay.mode' => 'mock',
            'services.ebay.client_id' => 'test-client-id',
            'services.ebay.client_secret' => 'test-client-secret',
        ]);

        Http::fake();

        // 1. Regular user in testing environment cannot switch mock -> production
        Sanctum::actingAs($this->user);
        $testingRes = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&driver=production&fresh=true&store=false');
        $testingRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_mock' => true,
                ],
            ]);
        Http::assertNothingSent();

        // 2. In production environment: EBAY_MODE=mock MUST fail closed (never MockEbayConnector, even with driver override)
        $this->app->detectEnvironment(fn () => 'production');

        $prodSearchRes = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&driver=production&fresh=true');
        $prodSearchRes->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);

        $prodItemRes = $this->getJson('/api/v1/market-research/ebay/items/v1%7CEBAY-PRINTER-101%7C0?driver=production&fresh=true');
        $prodItemRes->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);

        // Even admin in production cannot use EBAY_MODE=mock or override it dynamically
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $adminProdRes = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&driver=production&fresh=true');
        $adminProdRes->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame(0, ProductListing::count());
        $this->assertSame(0, CanonicalProduct::count());
        Http::assertNothingSent();

        // Direct service check in production must reject mock mode rather than returning MockEbayConnector
        $service = app(MarketResearchService::class);
        $this->expectException(EbayIntegrationException::class);
        $this->expectExceptionCode(503);
        $service->resolveConnector('production');
    }

    public function test_production_ebay_missing_credentials_fails_closed_without_mock_fallback_or_db_writes(): void
    {
        config([
            'services.ebay.mode' => 'production',
            'services.ebay.client_id' => '',
            'services.ebay.client_secret' => '',
        ]);

        Http::fake();
        $this->app->detectEnvironment(fn () => 'production');
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('missing in production', $response->json('message'));

        $this->assertSame(0, ProductListing::count());
        $this->assertSame(0, CanonicalProduct::count());
        Http::assertNothingSent();

        // Omitted EBAY_MODE in production with missing credentials must also fail closed, never defaulting to mock
        config(['services.ebay.mode' => null]);
        $service = app(MarketResearchService::class);
        $this->expectException(EbayIntegrationException::class);
        $this->expectExceptionCode(503);
        $service->resolveConnector();
    }

    public function test_admin_can_override_driver_only_in_testing_environment(): void
    {
        config([
            'services.ebay.mode' => 'mock',
            'services.ebay.client_id' => 'test-client-id',
            'services.ebay.client_secret' => 'test-client-secret',
        ]);

        Http::fake([
            'https://api.sandbox.ebay.com/identity/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox_token_123',
                'expires_in' => 7200,
            ], 200),
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::response([
                'total' => 1,
                'itemSummaries' => [
                    [
                        'itemId' => 'v1|SANDBOX-1|0',
                        'title' => 'Sandbox Wireless Printer',
                        'price' => ['value' => '149.00', 'currency' => 'USD'],
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&driver=sandbox&fresh=true&store=false');
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_mock' => false,
                ],
            ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.sandbox.ebay.com'));
    }

    public function test_ebay_search_returns_429_rate_limit_envelope_with_retry_after_header(): void
    {
        Sanctum::actingAs($this->user);

        $connector = new class implements MarketConnectorInterface {
            public function getSourceIdentifier(): string
            {
                return 'ebay';
            }

            public function searchRaw(string $query, array $parameters = []): array
            {
                throw new EbayRateLimitException(
                    'eBay API rate limit exceeded (429 Too Many Requests).',
                    45,
                    429,
                    ['errors' => [['message' => 'Too many requests']]]
                );
            }

            public function fetchDetailsRaw(string $externalProductId): ?array
            {
                return null;
            }

            public function checkHealth(): bool
            {
                return true;
            }
        };

        $this->app->instance(
            MarketResearchService::class,
            new MarketResearchService(
                app(ProductIngestionService::class),
                app(EbayProductNormalizer::class),
                $connector
            )
        );

        $response = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true');

        $response->assertStatus(429)
            ->assertHeader('Retry-After', '45')
            ->assertJson([
                'success' => false,
                'message' => 'eBay API rate limit exceeded (429 Too Many Requests).',
                'errors' => [
                    'errors' => [
                        ['message' => 'Too many requests'],
                    ],
                ],
            ]);
    }

    public function test_ebay_upstream_errors_return_standardized_envelope_and_map_upstream_auth_to_502(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Upstream eBay 503 Server Error
        $serverErrorConnector = new class implements MarketConnectorInterface {
            public function getSourceIdentifier(): string { return 'ebay'; }
            public function searchRaw(string $query, array $parameters = []): array
            {
                throw new EbayServerException('eBay servers unavailable.', 503, ['upstream' => '503']);
            }
            public function fetchDetailsRaw(string $externalProductId): ?array
            {
                throw new EbayClientException('Invalid eBay item ID.', 400, ['errors' => [['message' => 'Bad ID']]]);
            }
            public function checkHealth(): bool { return false; }
        };

        $this->app->instance(
            MarketResearchService::class,
            new MarketResearchService(
                app(ProductIngestionService::class),
                app(EbayProductNormalizer::class),
                $serverErrorConnector
            )
        );

        $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true')
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'eBay servers unavailable.',
                'errors' => ['upstream' => '503'],
            ]);

        $this->getJson('/api/v1/market-research/ebay/items/bad-id?fresh=true')
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid eBay item ID.',
                'errors' => ['errors' => [['message' => 'Bad ID']]],
            ]);

        // 2. Upstream eBay OAuth 401 must map to 502 Bad Gateway (never user 401 logout)
        $authErrorConnector = new class implements MarketConnectorInterface {
            public function getSourceIdentifier(): string { return 'ebay'; }
            public function searchRaw(string $query, array $parameters = []): array
            {
                throw new EbayAuthenticationException('Invalid eBay OAuth credentials.', 401, ['error' => 'invalid_client']);
            }
            public function fetchDetailsRaw(string $externalProductId): ?array
            {
                throw new EbayAuthenticationException('Invalid eBay OAuth credentials.', 401, ['error' => 'invalid_client']);
            }
            public function checkHealth(): bool { return false; }
        };

        $this->app->instance(
            MarketResearchService::class,
            new MarketResearchService(
                app(ProductIngestionService::class),
                app(EbayProductNormalizer::class),
                $authErrorConnector
            )
        );

        // Route::getController() caches the resolved controller instance across requests
        // within the same test method; flush it so the controller re-resolves the new service binding.
        foreach (Route::getRoutes() as $route) {
            $route->flushController();
        }

        $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&fresh=true')
            ->assertStatus(502)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid eBay OAuth credentials.',
                'errors' => ['error' => 'invalid_client'],
            ]);
    }

    public function test_ebay_item_not_found_returns_http_404(): void
    {
        Sanctum::actingAs($this->user);

        $nullConnector = new class implements MarketConnectorInterface {
            public function getSourceIdentifier(): string { return 'ebay'; }
            public function searchRaw(string $query, array $parameters = []): array { return []; }
            public function fetchDetailsRaw(string $externalProductId): ?array { return null; }
            public function checkHealth(): bool { return true; }
        };

        $this->app->instance(
            MarketResearchService::class,
            new MarketResearchService(
                app(ProductIngestionService::class),
                app(EbayProductNormalizer::class),
                $nullConnector
            )
        );

        $response = $this->getJson('/api/v1/market-research/ebay/items/v1%7CDOES-NOT-EXIST%7C0?fresh=true');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'eBay item [v1|DOES-NOT-EXIST|0] was not found.',
            ]);
    }
}

