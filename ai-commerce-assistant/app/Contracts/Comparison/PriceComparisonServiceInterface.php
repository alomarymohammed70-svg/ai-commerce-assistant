<?php

namespace App\Contracts\Comparison;

use App\DTOs\Comparison\PriceComparisonResultDto;
use App\Models\CanonicalProduct;

interface PriceComparisonServiceInterface
{
    /**
     * Compare all available offers for a canonical product across marketplaces.
     * Normalizes all currencies to the specified target base currency.
     *
     * @param CanonicalProduct $canonicalProduct
     * @param string|null $targetCurrency
     * @return PriceComparisonResultDto
     */
    public function compare(CanonicalProduct $canonicalProduct, ?string $targetCurrency = null): PriceComparisonResultDto;
}

