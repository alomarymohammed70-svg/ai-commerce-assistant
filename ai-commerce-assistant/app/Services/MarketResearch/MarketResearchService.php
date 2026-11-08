<?php

namespace App\Services\MarketResearch;

use App\Contracts\Marketplaces\MarketConnectorInterface;
use App\DTOs\ExternalSources\ExternalProductDto;
use App\Models\ProductListing;
use App\Services\ExternalSources\Connectors\EbayConnector;
use App\Services\ExternalSources\Connectors\MockEbayConnector;
use App\Services\ExternalSources\Normalizers\EbayProductNormalizer;
use App\Services\Product\ProductIngestionService;
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
     */
    public function resolveConnector(?string $driverOverride = null): MarketConnectorInterface
    {
        if ($this->connector !== null && $driverOverride === null) {
            return $this->connector;
        }

        $mode = strtolower((string) ($driverOverride ?? config('services.ebay.mode', 'mock')));
        $hasCredentials = ! empty(config('services.ebay.client_id')) && ! empty(config('services.ebay.client_secret'));

        // If explicitly mock or credentials not configured, use Mock connector
        if ($mode === 'mock' || ! $hasCredentials) {
            return new MockEbayConnector();
        }

        return new EbayConnector();
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
        $connector = $this->resolveConnector($parameters['driver'] ?? null);

        $limit = min(50, max(1, (int) ($parameters['limit'] ?? 10)));
        $offset = max(0, (int) ($parameters['offset'] ?? 0));

        $normParams = $parameters;
        ksort($normParams);
        $marketplaceId = (string) config('services.ebay.marketplace_id', 'EBAY_US');
        $cacheKey = 'ebay_search_' . md5($query . '_' . serialize($normParams) . '_' . $connector->getSourceIdentifier() . '_' . $marketplaceId);
        $cacheTtl = (int) config('services.ebay.cache_ttl_search', 900);

        $isCached = false;
        if (! $fresh && Cache::has($cacheKey)) {
            $rawResponse = Cache::get($cacheKey);
            $isCached = true;
        } else {
            $rawResponse = $connector->searchRaw($query, $parameters);
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
        $connector = $this->resolveConnector($parameters['driver'] ?? null);

        $marketplaceId = (string) config('services.ebay.marketplace_id', 'EBAY_US');
        $cacheKey = 'ebay_item_' . $marketplaceId . '_' . rawurlencode($externalProductId);
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
