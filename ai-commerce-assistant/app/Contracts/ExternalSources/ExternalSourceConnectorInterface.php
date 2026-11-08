<?php

namespace App\Contracts\ExternalSources;

use App\DTOs\ExternalSources\ExternalProductDto;

/**
 * Contract defining standard operations for any external marketplace connector.
 * Implementing this interface allows adding new sources (e.g. Amazon, Alibaba, AliExpress, eBay)
 * cleanly without altering the core pipeline.
 */
interface ExternalSourceConnectorInterface
{
    /**
     * Unique identifier of the source (e.g., 'amazon', 'alibaba', 'aliexpress', 'ebay').
     *
     * @return string
     */
    public function getSourceIdentifier(): string;

    /**
     * Search products in the external source and normalize results into standard DTOs.
     *
     * @param string $query
     * @param array<string, mixed> $parameters
     * @return array<int, ExternalProductDto>
     */
    public function search(string $query, array $parameters = []): array;

    /**
     * Fetch single product details by external source ID and normalize.
     *
     * @param string $externalProductId
     * @return ExternalProductDto|null
     */
    public function fetchDetails(string $externalProductId): ?ExternalProductDto;

    /**
     * Verify API connectivity and credential validity for this connector.
     *
     * @return bool
     */
    public function checkHealth(): bool;
}

