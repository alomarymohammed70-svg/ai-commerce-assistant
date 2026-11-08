<?php

namespace App\Contracts\Matching;

use App\DTOs\Matching\ProductMatchResult;
use App\Models\ProductListing;

interface ProductMatchingServiceInterface
{
    /**
     * Match a marketplace listing to existing CanonicalProducts.
     * Uses deterministic heuristics (model number, brand, category, title token similarity).
     *
     * @param ProductListing $listing
     * @return ProductMatchResult
     */
    public function matchListing(ProductListing $listing): ProductMatchResult;

    /**
     * Match arbitrary listing attributes against candidate canonical products.
     *
     * @param string $title
     * @param string|null $brand
     * @param string|null $modelNumber
     * @param int|null $categoryId
     * @return ProductMatchResult
     */
    public function matchAttributes(
        string $title,
        ?string $brand = null,
        ?string $modelNumber = null,
        ?int $categoryId = null
    ): ProductMatchResult;
}

