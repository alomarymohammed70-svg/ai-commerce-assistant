<?php

namespace Tests\Feature\Api\V1;

use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Models\ProductSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EbayMarketResearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_search_ebay_marketplace_and_ingest_listings(): void
    {
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
        $response = $this->getJson('/api/v1/market-research/ebay/search?q=wireless+printer&include_raw=true');

        $responseContent = $response->getContent();

        $this->assertStringNotContainsString('client_secret', $responseContent);
        $this->assertStringNotContainsString('ClientSecret', $responseContent);
        $this->assertStringNotContainsString('access_token', $responseContent);
        $this->assertStringNotContainsString('Authorization:', $responseContent);
    }
}

