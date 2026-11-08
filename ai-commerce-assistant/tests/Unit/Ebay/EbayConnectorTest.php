<?php

namespace Tests\Unit\Ebay;

use App\Contracts\ExternalSources\Ebay\EbayAuthServiceInterface;
use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayClientException;
use App\Exceptions\Ebay\EbayConnectionException;
use App\Exceptions\Ebay\EbayRateLimitException;
use App\Exceptions\Ebay\EbayServerException;
use App\Services\ExternalSources\Connectors\EbayConnector;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EbayConnectorTest extends TestCase
{
    protected EbayAuthServiceInterface $authMock;
    protected EbayConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authMock = new class implements EbayAuthServiceInterface {
            public int $invalidateCalls = 0;
            public function getAccessToken(bool $forceRefresh = false): string
            {
                return 'valid_mock_token_abc123';
            }
            public function invalidateCachedToken(): void
            {
                $this->invalidateCalls++;
            }
            public function hasConfiguredCredentials(): bool
            {
                return true;
            }
        };

        $this->connector = new EbayConnector(
            authService: $this->authMock,
            mode: 'sandbox',
            marketplaceId: 'EBAY_US',
            timeoutSeconds: 5,
            retryAttempts: 0 // Zero retries for direct unit assertions
        );
    }

    public function test_search_raw_constructs_correct_request_and_returns_payload(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::response([
                'total' => 1,
                'limit' => 10,
                'offset' => 0,
                'itemSummaries' => [
                    [
                        'itemId' => 'v1|123|0',
                        'title' => 'Laser Printer',
                        'price' => ['value' => '99.00', 'currency' => 'USD'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->connector->searchRaw('laser printer', ['limit' => 10]);

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['itemSummaries']);
        $this->assertEquals('v1|123|0', $result['itemSummaries'][0]['itemId']);

        Http::assertSent(function ($request) {
            return str_contains(urldecode($request->url()), 'q=laser printer')
                && $request->header('Authorization')[0] === 'Bearer valid_mock_token_abc123'
                && $request->header('X-EBAY-C-MARKETPLACE-ID')[0] === 'EBAY_US';
        });
    }

    public function test_fetch_details_raw_returns_item_payload(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item/v1%7C123%7C0' => Http::response([
                'itemId' => 'v1|123|0',
                'title' => 'Single Laser Printer Item',
            ], 200),
        ]);

        $item = $this->connector->fetchDetailsRaw('v1|123|0');

        $this->assertNotNull($item);
        $this->assertEquals('Single Laser Printer Item', $item['title']);
    }

    public function test_fetch_details_raw_returns_null_on_404(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item/*' => Http::response([
                'errors' => [['message' => 'Not found']],
            ], 404),
        ]);

        $item = $this->connector->fetchDetailsRaw('non_existent_id');
        $this->assertNull($item);
    }

    public function test_throws_rate_limit_exception_on_429_with_retry_after(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::response(
                ['message' => 'Too Many Requests'],
                429,
                ['Retry-After' => '120']
            ),
        ]);

        try {
            $this->connector->searchRaw('throttled query');
            $this->fail('Expected EbayRateLimitException was not thrown.');
        } catch (EbayRateLimitException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(120, $e->getRetryAfterSeconds());
        }
    }

    public function test_throws_client_exception_on_400(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::response([
                'errors' => [['message' => 'Invalid filter format']],
            ], 400),
        ]);

        $this->expectException(EbayClientException::class);
        $this->connector->searchRaw('bad filter');
    }

    public function test_throws_server_exception_on_502(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::response('Bad Gateway', 502),
        ]);

        $this->expectException(EbayServerException::class);
        $this->connector->searchRaw('server error test');
    }

    public function test_invalidates_token_and_retries_once_on_expired_401(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/buy/browse/v1/item_summary/search*' => Http::sequence()
                ->push(['error' => 'token_expired'], 401)
                ->push(['total' => 5, 'itemSummaries' => []], 200),
        ]);

        $result = $this->connector->searchRaw('query after expired token');

        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $this->authMock->invalidateCalls);
        Http::assertSentCount(2);
    }
}
