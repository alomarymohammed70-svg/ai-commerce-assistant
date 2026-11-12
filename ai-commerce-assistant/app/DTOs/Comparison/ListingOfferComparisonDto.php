<?php

namespace App\DTOs\Comparison;

class ListingOfferComparisonDto
{
    /**
     * @param array{username: ?string, store_name: ?string, feedback_percentage: ?float, feedback_score: ?int}|null $seller
     */
    public function __construct(
        public readonly int $listingId,
        public readonly int $sourceId,
        public readonly string $sourceCode,
        public readonly string $sourceName,
        public readonly string $externalId,
        public readonly string $title,
        public readonly ?string $url,
        public readonly ?string $imageUrl,
        public readonly string $originalPrice,
        public readonly string $originalCurrency,
        public readonly string $normalizedPrice,
        public readonly string $targetCurrency,
        public readonly string $exchangeRate,
        public readonly ?float $rating,
        public readonly int $reviewsCount,
        public readonly bool $isAvailable,
        public readonly ?string $observedAt,
        public readonly ?array $seller = null
    ) {}

    public function toArray(): array
    {
        return [
            'listing_id' => $this->listingId,
            'source' => [
                'id' => $this->sourceId,
                'code' => $this->sourceCode,
                'name' => $this->sourceName,
            ],
            'seller' => $this->seller,
            'external_id' => $this->externalId,
            'title' => $this->title,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'original_price' => $this->originalPrice,
            'original_currency' => $this->originalCurrency,
            'normalized_price' => $this->normalizedPrice,
            'target_currency' => $this->targetCurrency,
            'exchange_rate' => $this->exchangeRate,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'is_available' => $this->isAvailable,
            'observed_at' => $this->observedAt,
        ];
    }
}
