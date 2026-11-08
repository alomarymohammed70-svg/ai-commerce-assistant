<?php

namespace App\DTOs\ExternalSources;

/**
 * Data Transfer Object representing normalized product information
 * retrieved from any external marketplace (Amazon, Alibaba, AliExpress, eBay, etc.).
 */
class ExternalProductDto
{
    public function __construct(
        public readonly string $source,
        public readonly string $externalId,
        public readonly string $title,
        public readonly float $price,
        public readonly string $currency,
        public readonly string $url,
        public readonly ?string $description = null,
        public readonly ?string $brand = null,
        public readonly ?float $originalPrice = null,
        public readonly ?float $rating = null,
        public readonly ?int $reviewsCount = 0,
        public readonly ?string $supplierName = null,
        public readonly ?int $minOrderQuantity = 1,
        public readonly ?string $imageUrl = null,
        public readonly bool $isAvailable = true,
        public readonly ?string $categorySlug = null,
        public readonly array $rawPayload = []
    ) {}

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'external_id' => $this->externalId,
            'title' => $this->title,
            'price' => $this->price,
            'currency' => $this->currency,
            'url' => $this->url,
            'description' => $this->description,
            'brand' => $this->brand,
            'original_price' => $this->originalPrice,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'supplier_name' => $this->supplierName,
            'min_order_quantity' => $this->minOrderQuantity,
            'image_url' => $this->imageUrl,
            'is_available' => $this->isAvailable,
            'category_slug' => $this->categorySlug,
            'raw_payload' => $this->rawPayload,
        ];
    }

    /**
     * Create DTO from array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            source: (string) ($data['source'] ?? 'unknown'),
            externalId: (string) ($data['external_id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            price: (float) ($data['price'] ?? 0.0),
            currency: strtoupper((string) ($data['currency'] ?? 'USD')),
            url: (string) ($data['url'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            brand: isset($data['brand']) ? (string) $data['brand'] : null,
            originalPrice: isset($data['original_price']) ? (float) $data['original_price'] : null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            reviewsCount: isset($data['reviews_count']) ? (int) $data['reviews_count'] : 0,
            supplierName: $data['supplier_name'] ?? null,
            minOrderQuantity: isset($data['min_order_quantity']) ? (int) $data['min_order_quantity'] : 1,
            imageUrl: $data['image_url'] ?? null,
            isAvailable: (bool) ($data['is_available'] ?? true),
            categorySlug: $data['category_slug'] ?? null,
            rawPayload: (array) ($data['raw_payload'] ?? [])
        );
    }
}
