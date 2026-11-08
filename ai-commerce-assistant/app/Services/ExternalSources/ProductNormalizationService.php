<?php

namespace App\Services\ExternalSources;

use App\Contracts\ExternalSources\ProductNormalizerInterface;
use App\DTOs\ExternalSources\ExternalProductDto;
use App\Services\ExternalSources\Normalizers\AlibabaProductNormalizer;
use App\Services\ExternalSources\Normalizers\AliExpressProductNormalizer;
use App\Services\ExternalSources\Normalizers\AmazonProductNormalizer;
use App\Services\ExternalSources\Normalizers\EbayProductNormalizer;
use App\Services\ExternalSources\Normalizers\GenericProductNormalizer;

class ProductNormalizationService
{
    /**
     * Registered normalizers keyed by source identifier.
     *
     * @var array<string, ProductNormalizerInterface>
     */
    protected array $normalizers = [];

    /**
     * Fallback generic normalizer.
     */
    protected ProductNormalizerInterface $fallbackNormalizer;

    public function __construct()
    {
        $this->fallbackNormalizer = new GenericProductNormalizer;

        // Register default marketplace normalizers
        $this->registerNormalizer(new AmazonProductNormalizer);
        $this->registerNormalizer(new AlibabaProductNormalizer);
        $this->registerNormalizer(new AliExpressProductNormalizer);
        $this->registerNormalizer(new EbayProductNormalizer);
    }

    /**
     * Register a source normalizer.
     */
    public function registerNormalizer(ProductNormalizerInterface $normalizer): self
    {
        $this->normalizers[strtolower($normalizer->getSourceIdentifier())] = $normalizer;
        return $this;
    }

    /**
     * Normalize raw source payload into standard ExternalProductDto.
     *
     * @param string $source
     * @param array<string, mixed> $rawPayload
     * @return ExternalProductDto
     */
    public function normalize(string $source, array $rawPayload): ExternalProductDto
    {
        $key = strtolower(trim($source));

        $normalizer = $this->normalizers[$key] ?? $this->fallbackNormalizer;

        return $normalizer->normalize($rawPayload);
    }

    /**
     * Check if a specific source normalizer exists.
     */
    public function hasNormalizer(string $source): bool
    {
        return isset($this->normalizers[strtolower($source)]);
    }
}

