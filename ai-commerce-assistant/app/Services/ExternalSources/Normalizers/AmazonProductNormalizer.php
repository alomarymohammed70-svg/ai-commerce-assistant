<?php

namespace App\Services\ExternalSources\Normalizers;

use App\Contracts\ExternalSources\ProductNormalizerInterface;
use App\DTOs\ExternalSources\ExternalProductDto;

class AmazonProductNormalizer implements ProductNormalizerInterface
{
    public function getSourceIdentifier(): string
    {
        return 'amazon';
    }

    public function normalize(array $rawPayload): ExternalProductDto
    {
        $externalId = (string) ($rawPayload['asin'] ?? $rawPayload['external_id'] ?? $rawPayload['id'] ?? '');
        $title = (string) ($rawPayload['title'] ?? $rawPayload['product_title'] ?? $rawPayload['name'] ?? 'Untitled Amazon Product');
        $price = (float) ($rawPayload['price'] ?? $rawPayload['current_price'] ?? $rawPayload['buying_price'] ?? 0.0);
        $originalPrice = isset($rawPayload['original_price']) || isset($rawPayload['list_price'])
            ? (float) ($rawPayload['original_price'] ?? $rawPayload['list_price'])
            : null;
        $rating = isset($rawPayload['rating']) || isset($rawPayload['stars'])
            ? (float) ($rawPayload['rating'] ?? $rawPayload['stars'])
            : null;
        $reviewsCount = (int) ($rawPayload['reviews_count'] ?? $rawPayload['total_reviews'] ?? $rawPayload['ratings_total'] ?? 0);
        $url = (string) ($rawPayload['url'] ?? $rawPayload['detail_page_url'] ?? "https://www.amazon.com/dp/{$externalId}");
        $imageUrl = $rawPayload['image_url'] ?? $rawPayload['main_image'] ?? $rawPayload['image'] ?? null;
        $brand = $rawPayload['brand'] ?? $rawPayload['manufacturer'] ?? null;
        $description = $rawPayload['description'] ?? $rawPayload['features'] ?? null;

        return new ExternalProductDto(
            source: 'amazon',
            externalId: $externalId,
            title: $title,
            price: $price,
            currency: strtoupper((string) ($rawPayload['currency'] ?? 'USD')),
            url: $url,
            description: is_array($description) ? implode("\n", $description) : $description,
            brand: $brand,
            originalPrice: $originalPrice,
            rating: $rating,
            reviewsCount: $reviewsCount,
            supplierName: $rawPayload['seller_name'] ?? 'Amazon Seller',
            minOrderQuantity: 1,
            imageUrl: $imageUrl,
            isAvailable: (bool) ($rawPayload['is_available'] ?? $rawPayload['in_stock'] ?? true),
            categorySlug: $rawPayload['category_slug'] ?? null,
            rawPayload: $rawPayload
        );
    }
}

