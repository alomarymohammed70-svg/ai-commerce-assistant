<?php

namespace App\Services\MarketResearch;

use App\Contracts\Marketplaces\MarketConnectorInterface;
use App\DTOs\ExternalSources\ExternalProductDto;
use App\Exceptions\Ebay\EbayIntegrationException;
use App\Models\ProductListing;
use App\Services\ExternalSources\Connectors\EbayConnector;
use App\Services\ExternalSources\Connectors\MockEbayConnector;
use App\Services\ExternalSources\Ebay\EbayAuthService;
use App\Services\ExternalSources\Normalizers\EbayProductNormalizer;
use App\Services\Product\ProductIngestionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketResearchService
{
    public function __construct(
        protected ProductIngestionService $ingestionService,
        protected EbayProductNormalizer $normalizer,
        protected ?MarketConnectorInterface $connector = null
    ) {}

    /**
     * Resolve the active eBay connector based on configuration and environment mode.
     * In production, server configuration (services.ebay.mode) strictly overrides any driverOverride,
     * mock mode is forbidden, and missing credentials fail closed instead of falling back to Mock.
     *
     * @throws EbayIntegrationException
     */
    public function resolveConnector(?string $driverOverride = null): MarketConnectorInterface
    {
        $isProduction = app()->environment('production');
        $effectiveOverride = $driverOverride;

        if ($isProduction) {
            if ($effectiveOverride !== null && trim($effectiveOverride) !== '') {
                Log::warning('ebay.service_driver_override_blocked_in_production', [
                    'requested_driver' => $effectiveOverride,
                    'configured_mode' => config('services.ebay.mode', 'production'),
                ]);
            }
            $effectiveOverride = null;
        }

        if ($this->connector !== null && ($effectiveOverride === null || trim($effectiveOverride) === '')) {
            if ($isProduction && $this->connector instanceof MockEbayConnector) {
                Log::error('ebay.mock_connector_blocked_in_production');

                throw new EbayIntegrationException(
                    'Mock eBay connector is not permitted in production.',
                    503
                );
            }

            return $this->connector;
        }

        $mode = $this->resolveEffectiveMode($effectiveOverride);
        $hasCredentials = ! empty(trim((string) config('services.ebay.client_id')))
            && ! empty(trim((string) config('services.ebay.client_secret')));

        if ($isProduction) {
            if (! in_array($mode, ['sandbox', 'production'], true)) {
                Log::error('ebay.mock_mode_blocked_in_production', [
                    'configured_mode' => $mode,
                ]);

                throw new EbayIntegrationException(
                    'eBay integration cannot run in mock or unconfigured mode in production. Set EBAY_MODE=production and provide valid eBay credentials.',
                    503
                );
            }

            if (! $hasCredentials) {
                Log::error('ebay.missing_credentials_in_production', [
                    'configured_mode' => $mode,
                ]);

                throw new EbayIntegrationException(
                    'eBay API credentials (EBAY_CLIENT_ID / EBAY_CLIENT_SECRET) are missing in production and cannot fall back to mock.',
                    503
                );
            }

            return new EbayConnector(
                authService: new EbayAuthService(mode: $mode),
                mode: $mode
            );
        }

        // Non-production (local / testing): preserve mock behavior when explicitly mock or credentials absent
        if ($mode === 'mock' || ! $hasCredentials) {
            return new MockEbayConnector();
        }

        $liveMode = in_array($mode, ['sandbox', 'production'], true) ? $mode : 'sandbox';

        return new EbayConnector(
            authService: new EbayAuthService(mode: $liveMode),
            mode: $liveMode
        );
    }

    protected function resolveEffectiveMode(?string $driverOverride = null): string
    {
        if (app()->environment('production') || $driverOverride === null || trim($driverOverride) === '') {
            $defaultMode = app()->environment('production') ? 'production' : 'mock';
            $configuredMode = config('services.ebay.mode');

            if ($configuredMode === null || trim((string) $configuredMode) === '') {
                return $defaultMode;
            }

            return strtolower(trim((string) $configuredMode));
        }

        return strtolower(trim($driverOverride));
    }

    /**
     * Search eBay marketplace products with caching, normalization, and optional persistence.
     *
     * @param string $query
     * @param array<string, mixed> $parameters
     * @param bool $fresh Bypass cache if true
     * @param bool $shouldStore Persist normalized listings into database if true
     * @return array<string, mixed>
     */
    public function searchEbay(
        string $query,
        array $parameters = [],
        bool $fresh = false,
        bool $shouldStore = true
    ): array {
        $startTime = microtime(true);
        $requestedDriver = isset($parameters['driver']) ? (string) $parameters['driver'] : null;
        $connector = $this->resolveConnector($requestedDriver);
        $effectiveMode = $this->resolveEffectiveMode($requestedDriver);

        $limit = min(50, max(1, (int) ($parameters['limit'] ?? 10)));
        $offset = max(0, (int) ($parameters['offset'] ?? 0));

        $normParams = Arr::except($parameters, ['driver']);
        ksort($normParams);
        $marketplaceId = (string) config('services.ebay.marketplace_id', 'EBAY_US');
        $cacheKey = 'ebay_search_' . md5($query . '_' . serialize($normParams) . '_' . $connector->getSourceIdentifier() . '_' . $effectiveMode . '_' . $marketplaceId);
        $cacheTtl = (int) config('services.ebay.cache_ttl_search', 900);

        $isCached = false;
        if (! $fresh && Cache::has($cacheKey)) {
            $rawResponse = Cache::get($cacheKey);
            $isCached = true;
        } else {
            $rawResponse = $connector->searchRaw($query, $normParams);
            Cache::put($cacheKey, $rawResponse, $cacheTtl);
        }

        $itemsRaw = $rawResponse['itemSummaries'] ?? [];
        $totalFound = (int) ($rawResponse['total'] ?? count($itemsRaw));

        $dtos = [];
        $listings = [];

        foreach ($itemsRaw as $rawItem) {
            $dto = $this->normalizer->normalize($rawItem);
            $dtos[] = $dto;

            if ($shouldStore && ! empty($dto->externalId)) {
                $listing = $this->ingestionService->ingestFromDto($dto);
                $listing->load(['source', 'category']);
                $listings[] = $listing;
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'query' => $query,
            'total' => $totalFound,
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($dtos),
            'duration_ms' => $durationMs,
            'cached' => $isCached,
            'source' => $connector->getSourceIdentifier(),
            'is_mock' => (bool) ($rawResponse['is_mock'] ?? false),
            'listings' => $listings,
            'dtos' => $dtos,
            'raw_payload' => $rawResponse,
        ];
    }

    /**
     * Fetch single eBay item details with caching, normalization, and optional persistence.
     *
     * @param string $externalProductId
     * @param array<string, mixed> $parameters
     * @param bool $fresh
     * @param bool $shouldStore
     * @return array<string, mixed>|null
     */
    public function getEbayItem(
        string $externalProductId,
        array $parameters = [],
        bool $fresh = false,
        bool $shouldStore = true
    ): ?array {
        $startTime = microtime(true);
        $requestedDriver = isset($parameters['driver']) ? (string) $parameters['driver'] : null;
        $connector = $this->resolveConnector($requestedDriver);
        $effectiveMode = $this->resolveEffectiveMode($requestedDriver);

        $marketplaceId = (string) config('services.ebay.marketplace_id', 'EBAY_US');
        $cacheKey = 'ebay_item_' . $marketplaceId . '_' . $effectiveMode . '_' . rawurlencode($externalProductId);
        $cacheTtl = (int) config('services.ebay.cache_ttl_item', 3600);

        $isCached = false;
        if (! $fresh && Cache::has($cacheKey)) {
            $rawItem = Cache::get($cacheKey);
            $isCached = true;
        } else {
            $rawItem = $connector->fetchDetailsRaw($externalProductId);
            if ($rawItem !== null) {
                Cache::put($cacheKey, $rawItem, $cacheTtl);
            }
        }

        if ($rawItem === null) {
            return null;
        }

        $dto = $this->normalizer->normalize($rawItem);
        $listing = null;

        if ($shouldStore && ! empty($dto->externalId)) {
            $listing = $this->ingestionService->ingestFromDto($dto);
            $listing->load(['source', 'category']);
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'external_id' => $externalProductId,
            'duration_ms' => $durationMs,
            'cached' => $isCached,
            'source' => $connector->getSourceIdentifier(),
            'is_mock' => (bool) ($rawItem['is_mock'] ?? false),
            'listing' => $listing,
            'dto' => $dto,
            'raw_payload' => $rawItem,
        ];
    }
}
