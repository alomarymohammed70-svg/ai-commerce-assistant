<?php

namespace Tests\Unit\Ebay;

use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayServerException;
use App\Services\ExternalSources\Ebay\EbayAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EbayAuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_acquires_and_caches_oauth_token_successfully(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/identity/v1/oauth2/token' => Http::response([
                'access_token' => 'mock_token_123456789',
                'expires_in' => 7200,
                'token_type' => 'Application Access Token',
            ], 200),
        ]);

        $service = new EbayAuthService('sandbox', 'dummy_client_id', 'dummy_client_secret');
        $token = $service->getAccessToken();

        $this->assertEquals('mock_token_123456789', $token);

        // Subsequent call should hit cache and NOT issue a second HTTP request
        $token2 = $service->getAccessToken();
        $this->assertEquals('mock_token_123456789', $token2);

        Http::assertSentCount(1);
    }

    public function test_throws_exception_when_credentials_are_missing(): void
    {
        $this->expectException(EbayAuthenticationException::class);
        $this->expectExceptionMessage('EBAY_CLIENT_ID / EBAY_CLIENT_SECRET');

        $service = new EbayAuthService('sandbox', '', '');
        $service->getAccessToken();
    }

    public function test_throws_authentication_exception_on_invalid_credentials_401(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/identity/v1/oauth2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'client authentication failed',
            ], 401),
        ]);

        $this->expectException(EbayAuthenticationException::class);
        $this->expectExceptionMessage('Invalid eBay credentials');

        $service = new EbayAuthService('sandbox', 'bad_client_id', 'bad_secret');
        $service->getAccessToken();
    }

    public function test_throws_server_exception_on_identity_service_failure_500(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/identity/v1/oauth2/token' => Http::response('Server Error', 500),
        ]);

        $this->expectException(EbayServerException::class);

        $service = new EbayAuthService('sandbox', 'client_id', 'secret');
        $service->getAccessToken();
    }

    public function test_invalidate_cached_token_forces_new_network_request(): void
    {
        Http::fake([
            'https://api.sandbox.ebay.com/identity/v1/oauth2/token' => Http::sequence()
                ->push(['access_token' => 'token_v1', 'expires_in' => 7200])
                ->push(['access_token' => 'token_v2', 'expires_in' => 7200]),
        ]);

        $service = new EbayAuthService('sandbox', 'client_id', 'secret');
        $token1 = $service->getAccessToken();
        $this->assertEquals('token_v1', $token1);

        $service->invalidateCachedToken();

        $token2 = $service->getAccessToken();
        $this->assertEquals('token_v2', $token2);

        Http::assertSentCount(2);
    }
}

