<?php

namespace App\Services\ExternalSources\Normalizers;

use App\Contracts\ExternalSources\ProductNormalizerInterface;
use App\DTOs\ExternalSources\ExternalProductDto;

class AliExpressProductNormalizer implements ProductNormalizerInterface
{
    public function getSourceIdentifier(): string
    {
        return 'aliexpress';
    }

    public function normalize(array $rawPayload): ExternalProductDto
    {
        $externalId = (string) ($rawPayload['item_id'] ?? $rawPayload['product_id'] ?? $rawPayload['id'] ?? '');
        $title = (string) ($rawPayload['title'] ?? $rawPayload['product_title'] ?? 'AliExpress Item');
        $price = (float) ($rawPayload['target_sale_price'] ?? $rawPayload['current_price'] ?? $rawPayload['price'] ?? 0.0);
        $originalPrice = isset($rawPayload['original_price']) ? (float) $rawPayload['original_price'] : null;
        $rating = isset($rawPayload['evaluate_rate'])
            ? round(((float) $rawPayload['evaluate_rate']) / 20.0, 2) // Often given as percentage (e.g. 96% -> 4.8)
            : (isset($rawPayload['rating']) ? (float) $rawPayload['rating'] : null);
        $reviewsCount = (int) ($rawPayload['order_count'] ?? $rawPayload['feedback_count'] ?? 0);
        $url = (string) ($rawPayload['product_detail_url'] ?? $rawPayload['url'] ?? "https://www.aliexpress.com/item/{$externalId}.html");
        $imageUrl = $rawPayload['product_main_image_url'] ?? $rawPayload['image_url'] ?? null;

        return new ExternalProductDto(
            source: 'aliexpress',
            externalId: $externalId,
            title: $title,
            price: $price,
            currency: strtoupper((string) ($rawPayload['currency'] ?? 'USD')),
            url: $url,
            description: $rawPayload['description'] ?? null,
            brand: $rawPayload['store_name'] ?? null,
            originalPrice: $originalPrice,
            rating: $rating,
            reviewsCount: $reviewsCount,
            supplierName: $rawPayload['store_name'] ?? 'AliExpress Store',
            minOrderQuantity: 1,
            imageUrl: $imageUrl,
            isAvailable: (bool) ($rawPayload['in_stock'] ?? true),
            categorySlug: $rawPayload['category_slug'] ?? null,
            rawPayload: $rawPayload
        );
    }
}

