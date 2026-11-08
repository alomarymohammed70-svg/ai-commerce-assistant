<?php

namespace App\Services\ExternalSources\Connectors;

use App\Contracts\ExternalSources\Ebay\EbayAuthServiceInterface;
use App\Contracts\Marketplaces\MarketConnectorInterface;
use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayClientException;
use App\Exceptions\Ebay\EbayConnectionException;
use App\Exceptions\Ebay\EbayRateLimitException;
use App\Exceptions\Ebay\EbayServerException;
use App\Services\ExternalSources\Ebay\EbayAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayConnector implements MarketConnectorInterface
{
    protected string $mode;
    protected string $marketplaceId;
    protected int $timeoutSeconds;
    protected int $retryAttempts;
    protected int $retryBackoffMs;

    public function __construct(
        protected ?EbayAuthServiceInterface $authService = null,
        ?string $mode = null,
        ?string $marketplaceId = null,
        int $timeoutSeconds = 15,
        int $retryAttempts = 2,
        int $retryBackoffMs = 500
    ) {
        $this->authService = $authService ?? new EbayAuthService();
        $this->mode = strtolower((string) ($mode ?? config('services.ebay.mode', 'mock')));
        $this->marketplaceId = (string) ($marketplaceId ?? config('services.ebay.marketplace_id', 'EBAY_US'));
        $this->timeoutSeconds = $timeoutSeconds > 0 ? $timeoutSeconds : (int) config('services.ebay.timeout', 15);
        $this->retryAttempts = $retryAttempts >= 0 ? $retryAttempts : (int) config('services.ebay.retry_attempts', 2);
        $this->retryBackoffMs = $retryBackoffMs > 0 ? $retryBackoffMs : (int) config('services.ebay.retry_backoff_ms', 500);
    }

    public function getSourceIdentifier(): string
    {
        return 'ebay';
    }

    /**
     * Search products on eBay Browse API returning raw payload.
     */
    public function searchRaw(string $query, array $parameters = []): array
    {
        $limit = min(50, max(1, (int) ($parameters['limit'] ?? 10)));
        $offset = max(0, (int) ($parameters['offset'] ?? 0));

        $queryParams = [
            'q' => $query,
            'limit' => $limit,
            'offset' => $offset,
        ];

        // Build optional eBay Browse API filter string (e.g. price:[10..50],priceCurrency:USD)
        $filters = [];
        if (isset($parameters['min_price']) || isset($parameters['max_price'])) {
            $min = isset($parameters['min_price']) ? (float) $parameters['min_price'] : '';
            $max = isset($parameters['max_price']) ? (float) $parameters['max_price'] : '';
            $currency = strtoupper((string) ($parameters['currency'] ?? 'USD'));
            $filters[] = "price:[{$min}..{$max}],priceCurrency:{$currency}";
        }

        if (! empty($parameters['condition'])) {
            $filters[] = 'conditions:{' . strtoupper(trim((string) $parameters['condition'])) . '}';
        }

        if (! empty($filters)) {
            $queryParams['filter'] = implode(',', $filters);
        }

        return $this->executeRequest('GET', '/item_summary/search', $queryParams);
    }

    /**
     * Fetch single item details from eBay Browse API returning raw payload.
     */
    public function fetchDetailsRaw(string $externalProductId): ?array
    {
        if (empty($externalProductId)) {
            return null;
        }

        $encodedId = rawurlencode($externalProductId);

        try {
            return $this->executeRequest('GET', "/item/{$encodedId}");
        } catch (EbayClientException $e) {
            if ($e->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    public function checkHealth(): bool
    {
        if (! $this->authService->hasConfiguredCredentials()) {
            return false;
        }

        try {
            $token = $this->authService->getAccessToken();
            return ! empty($token);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Execute an authenticated HTTP request against eBay Browse API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @param bool $isRetryAfterReauth
     * @return array<string, mixed>
     */
    protected function executeRequest(
        string $method,
        string $endpoint,
        array $params = [],
        bool $isRetryAfterReauth = false
    ): array {
        $url = $this->getBaseUrl() . $endpoint;
        $accessToken = $this->authService->getAccessToken();

        Log::info('ebay.request.started', [
            'method' => $method,
            'endpoint' => $endpoint,
            'params' => array_diff_key($params, ['access_token' => '', 'token' => '']),
            'mode' => $this->mode,
        ]);

        $startTime = microtime(true);

        try {
            $client = $this->createHttpClient($accessToken);

            $response = match (strtoupper($method)) {
                'POST' => $client->post($url, $params),
                default => $client->get($url, $params),
            };
        } catch (ConnectionException $e) {
            Log::error('ebay.timeout', [
                'endpoint' => $endpoint,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
            ]);

            throw new EbayConnectionException(
                "Connection to eBay API timed out or was unreachable: {$e->getMessage()}",
                504,
                null,
                $e
            );
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // 1. Handle Token Expiration (401 Unauthorized)
        if ($response->status() === 401 && ! $isRetryAfterReauth) {
            Log::warning('ebay.authentication_failed', [
                'reason' => '401 Unauthorized from Browse API. Invalidating token and retrying once.',
            ]);

            $this->authService->invalidateCachedToken();
            return $this->executeRequest($method, $endpoint, $params, true);
        }

        // 2. Handle Rate Limit (429 Too Many Requests)
        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            $retrySeconds = is_numeric($retryAfter) ? (int) $retryAfter : null;

            Log::warning('ebay.rate_limited', [
                'status' => 429,
                'retry_after' => $retrySeconds,
                'endpoint' => $endpoint,
            ]);

            throw new EbayRateLimitException(
                'eBay API rate limit exceeded (429 Too Many Requests).',
                $retrySeconds,
                429,
                $response->json()
            );
        }

        // 3. Handle 401 / 403 Authentication / Authorization Errors
        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('ebay.authentication_failed', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
            ]);

            throw new EbayAuthenticationException(
                'eBay API authentication rejected access.',
                $response->status(),
                $response->json()
            );
        }

        // 4. Handle 4xx Client Errors
        if ($response->clientError()) {
            Log::warning('ebay.request.failed', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
                'error_payload' => $response->json(),
            ]);

            throw new EbayClientException(
                "eBay API client error ({$response->status()}): " . ($response->json('errors.0.message') ?? 'Bad Request'),
                $response->status(),
                $response->json()
            );
        }

        // 5. Handle 5xx Upstream Server Errors
        if ($response->serverError()) {
            Log::error('ebay.request.failed', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
            ]);

            throw new EbayServerException(
                "eBay servers returned a server error ({$response->status()}).",
                $response->status(),
                $response->json()
            );
        }

        Log::info('ebay.request.completed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'duration_ms' => $durationMs,
        ]);

        return $response->json() ?? [];
    }

    protected function createHttpClient(string $accessToken): PendingRequest
    {
        $client = Http::timeout($this->timeoutSeconds)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'X-EBAY-C-MARKETPLACE-ID' => $this->marketplaceId,
                'Content-Type' => 'application/json',
            ]);

        if ($this->retryAttempts > 0) {
            // Only retry on transient upstream 5xx errors or network exceptions
            $client->retry(
                $this->retryAttempts,
                $this->retryBackoffMs,
                function (\Throwable $exception, ?Response $response) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    return $response && in_array($response->status(), [500, 502, 503, 504], true);
                },
                throw: false
            );
        }

        return $client;
    }

    protected function getBaseUrl(): string
    {
        return $this->mode === 'production'
            ? 'https://api.ebay.com/buy/browse/v1'
            : 'https://api.sandbox.ebay.com/buy/browse/v1';
    }
}

