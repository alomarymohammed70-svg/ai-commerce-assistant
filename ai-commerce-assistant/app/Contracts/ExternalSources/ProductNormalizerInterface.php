<?php

namespace App\Contracts\ExternalSources;

use App\DTOs\ExternalSources\ExternalProductDto;

/**
 * Contract for normalizing source-specific raw external payloads into
 * standard unified ExternalProductDto instances.
 */
interface ProductNormalizerInterface
{
    /**
     * Unique source identifier (e.g. 'amazon', 'alibaba', 'aliexpress', 'ebay').
     */
    public function getSourceIdentifier(): string;

    /**
     * Transform arbitrary raw external data into a normalized DTO.
     *
     * @param array<string, mixed> $rawPayload
     * @return ExternalProductDto
     */
    public function normalize(array $rawPayload): ExternalProductDto;
}

