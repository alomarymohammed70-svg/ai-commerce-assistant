<?php

namespace App\Services\ExternalSources\Ebay;

use App\Contracts\ExternalSources\Ebay\EbayAuthServiceInterface;
use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayConnectionException;
use App\Exceptions\Ebay\EbayServerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayAuthService implements EbayAuthServiceInterface
{
    protected string $mode;
    protected string $clientId;
    protected string $clientSecret;
    protected int $timeoutSeconds;

    public function __construct(
        ?string $mode = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        int $timeoutSeconds = 15
    ) {
        $this->mode = strtolower((string) ($mode ?? config('services.ebay.mode', 'mock')));
        $this->clientId = trim((string) ($clientId ?? config('services.ebay.client_id', '')));
        $this->clientSecret = trim((string) ($clientSecret ?? config('services.ebay.client_secret', '')));
        $this->timeoutSeconds = $timeoutSeconds > 0 ? $timeoutSeconds : (int) config('services.ebay.timeout', 15);
    }

    public function getAccessToken(bool $forceRefresh = false): string
    {
        $cacheKey = $this->getCacheKey();

        if (! $forceRefresh && Cache::has($cacheKey)) {
            $cachedToken = Cache::get($cacheKey);
            if (! empty($cachedToken) && is_string($cachedToken)) {
                return $cachedToken;
            }
        }

        return $this->requestNewAccessToken();
    }

    public function invalidateCachedToken(): void
    {
        Cache::forget($this->getCacheKey());
    }

    public function hasConfiguredCredentials(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    /**
     * Request a fresh OAuth 2.0 application access token from eBay identity service.
     */
    protected function requestNewAccessToken(): string
    {
        if (! $this->hasConfiguredCredentials()) {
            Log::warning('ebay.authentication_failed', [
                'reason' => 'Missing Client ID or Client Secret in configuration.',
                'mode' => $this->mode,
            ]);

            throw new EbayAuthenticationException(
                'eBay API credentials (EBAY_CLIENT_ID / EBAY_CLIENT_SECRET) are missing or not configured.'
            );
        }

        $endpoint = $this->getOAuthEndpoint();
        $basicAuth = base64_encode("{$this->clientId}:{$this->clientSecret}");

        Log::info('ebay.request.started', [
            'action' => 'oauth_token_request',
            'endpoint' => $endpoint,
            'mode' => $this->mode,
        ]);

        try {
            $response = Http::asForm()
                ->timeout($this->timeoutSeconds)
                ->withHeaders([
                    'Authorization' => "Basic {$basicAuth}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->post($endpoint, [
                    'grant_type' => 'client_credentials',
                    'scope' => 'https://api.ebay.com/oauth/api_scope',
                ]);
        } catch (ConnectionException $e) {
            Log::error('ebay.timeout', [
                'action' => 'oauth_token_request',
                'message' => $e->getMessage(),
            ]);

            throw new EbayConnectionException(
                'Connection to eBay OAuth identity server timed out.',
                504,
                null,
                $e
            );
        }

        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('ebay.authentication_failed', [
                'status' => $response->status(),
                'mode' => $this->mode,
            ]);

            throw new EbayAuthenticationException(
                'Invalid eBay credentials. Identity server rejected Client ID or Client Secret.',
                $response->status(),
                $response->json()
            );
        }

        if ($response->serverError()) {
            Log::error('ebay.request.failed', [
                'action' => 'oauth_token_request',
                'status' => $response->status(),
            ]);

            throw new EbayServerException(
                'eBay identity server encountered an internal server error during token generation.',
                $response->status(),
                $response->json()
            );
        }

        if (! $response->successful()) {
            throw new EbayAuthenticationException(
                'Failed to acquire eBay OAuth token: ' . ($response->json('error_description') ?? 'Unknown error'),
                $response->status(),
                $response->json()
            );
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 7200);

        if (empty($accessToken) || ! is_string($accessToken)) {
            throw new EbayAuthenticationException('eBay OAuth response did not contain a valid access token.');
        }

        // Cache the token with safety buffer (5 minutes before official expiration)
        $cacheTtlSeconds = max(60, $expiresIn - 300);
        Cache::put($this->getCacheKey(), $accessToken, $cacheTtlSeconds);

        Log::info('ebay.request.completed', [
            'action' => 'oauth_token_request',
            'mode' => $this->mode,
            'expires_in' => $expiresIn,
            'cached_for_seconds' => $cacheTtlSeconds,
        ]);

        return $accessToken;
    }

    protected function getOAuthEndpoint(): string
    {
        return $this->mode === 'production'
            ? 'https://api.ebay.com/identity/v1/oauth2/token'
            : 'https://api.sandbox.ebay.com/identity/v1/oauth2/token';
    }

    protected function getCacheKey(): string
    {
        return "ebay_oauth_token_{$this->mode}_" . substr(hash('sha256', $this->clientId), 0, 12);
    }
}

