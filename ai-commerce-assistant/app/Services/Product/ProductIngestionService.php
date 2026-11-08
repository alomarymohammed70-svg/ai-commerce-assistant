<?php

namespace App\Services\Product;

use App\Contracts\Matching\ProductMatchingServiceInterface;
use App\DTOs\ExternalSources\ExternalProductDto;
use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Services\ExternalSources\ProductNormalizationService;
use App\Services\Matching\ProductMatchingService;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductIngestionService
{
    public function __construct(
        protected ProductNormalizationService $normalizationService,
        protected ?ProductMatchingServiceInterface $matchingService = null
    ) {
        $this->matchingService = $matchingService ?? new ProductMatchingService();
    }

    /**
     * Ingest and persist a product listing from raw external payload.
     *
     * Pipeline: Raw Payload -> Normalizer -> DTO -> Listing -> Matching -> Canonical Product
     *
     * @param string $sourceCode
     * @param array<string, mixed> $rawPayload
     * @param int|null $categoryId
     * @return Product
     */
    public function ingestFromRaw(string $sourceCode, array $rawPayload, ?int $categoryId = null): Product
    {
        $dto = $this->normalizationService->normalize($sourceCode, $rawPayload);

        return $this->ingestFromDto($dto, $categoryId);
    }

    /**
     * Ingest and persist a normalized ExternalProductDto.
     * Upserts the ProductListing and deterministic heuristic matching to a CanonicalProduct.
     *
     * @param ExternalProductDto $dto
     * @param int|null $categoryId
     * @return Product
     */
    public function ingestFromDto(ExternalProductDto $dto, ?int $categoryId = null): Product
    {
        if (empty($dto->externalId)) {
            throw new InvalidArgumentException('External product ID cannot be empty during ingestion.');
        }

        // 1. Resolve or create the ProductSource
        $source = ProductSource::firstOrCreate(
            ['code' => strtolower($dto->source)],
            [
                'name' => ucfirst($dto->source) . ' Marketplace',
                'is_active' => true,
            ]
        );

        // 2. Resolve Category if explicitly passed or via DTO category_slug
        $resolvedCategoryId = $categoryId;
        if (! $resolvedCategoryId && ! empty($dto->categorySlug)) {
            $cat = Category::where('slug', $dto->categorySlug)->first();
            $resolvedCategoryId = $cat?->id;
        }

        // 3. Upsert ProductListing record idempotently (source_id + external_id)
        /** @var Product $listing */
        $listing = Product::updateOrCreate(
            [
                'source_id' => $source->id,
                'external_id' => $dto->externalId,
            ],
            [
                'category_id' => $resolvedCategoryId,
                'title' => $dto->title,
                'description' => $dto->description,
                'brand' => $dto->brand,
                'url' => $dto->url,
                'image_url' => $dto->imageUrl,
                'currency' => strtoupper($dto->currency ?: 'USD'),
                'price' => $dto->price,
                'original_price' => $dto->originalPrice,
                'rating' => $dto->rating,
                'reviews_count' => $dto->reviewsCount ?? 0,
                'is_available' => $dto->isAvailable,
                'raw_payload' => $dto->rawPayload,
                'observed_at' => now(),
            ]
        );

        // 4. Matching Step: Associate listing with CanonicalProduct if not already linked
        if (! $listing->canonical_product_id) {
            $matchResult = $this->matchingService->matchListing($listing);

            if ($matchResult->isMatch() && $matchResult->canonicalProduct) {
                $listing->canonical_product_id = $matchResult->canonicalProduct->id;
                $listing->save();
            } else {
                // Create new Canonical Product for this unassociated offer
                $baseSlug = Str::slug($listing->title) ?: 'product';
                $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(6));
                $modelNumber = method_exists($this->matchingService, 'extractModelNumber')
                    ? $this->matchingService->extractModelNumber($listing->title)
                    : null;

                $canonical = CanonicalProduct::create([
                    'category_id' => $listing->category_id,
                    'name' => $listing->title,
                    'slug' => $uniqueSlug,
                    'brand' => $listing->brand,
                    'model_number' => $modelNumber,
                    'description' => $listing->description,
                    'base_currency' => $listing->currency ?: 'USD',
                ]);

                $listing->canonical_product_id = $canonical->id;
                $listing->save();
            }
        }

        return $listing;
    }
}
