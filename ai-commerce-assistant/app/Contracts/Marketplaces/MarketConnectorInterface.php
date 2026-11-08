<?php

namespace App\Contracts\Marketplaces;

interface MarketConnectorInterface
{
    /**
     * Unique marketplace source code (e.g. 'ebay', 'amazon', 'alibaba').
     */
    public function getSourceIdentifier(): string;

    /**
     * Search products in the external marketplace and return the raw API response.
     *
     * @param string $query
     * @param array<string, mixed> $parameters (limit, offset, filter, etc.)
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\Ebay\EbayIntegrationException
     */
    public function searchRaw(string $query, array $parameters = []): array;

    /**
     * Fetch single product details from the external marketplace in raw JSON/array format.
     *
     * @param string $externalProductId
     * @return array<string, mixed>|null
     *
     * @throws \App\Exceptions\Ebay\EbayIntegrationException
     */
    public function fetchDetailsRaw(string $externalProductId): ?array;

    /**
     * Verify marketplace API connectivity and credential validity.
     *
     * @return bool
     */
    public function checkHealth(): bool;
}

