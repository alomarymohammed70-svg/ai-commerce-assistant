<?php

namespace App\Services\ExternalSources\Normalizers;

use App\Contracts\ExternalSources\ProductNormalizerInterface;
use App\DTOs\ExternalSources\ExternalProductDto;

class AlibabaProductNormalizer implements ProductNormalizerInterface
{
    public function getSourceIdentifier(): string
    {
        return 'alibaba';
    }

    public function normalize(array $rawPayload): ExternalProductDto
    {
        $externalId = (string) ($rawPayload['product_id'] ?? $rawPayload['item_id'] ?? $rawPayload['id'] ?? '');
        $title = (string) ($rawPayload['subject'] ?? $rawPayload['title'] ?? 'Alibaba Wholesale Item');
        $price = (float) ($rawPayload['price_usd'] ?? $rawPayload['fob_price'] ?? $rawPayload['unit_price'] ?? $rawPayload['price'] ?? 0.0);
        $minOrder = (int) ($rawPayload['moq'] ?? $rawPayload['min_order_quantity'] ?? 1);
        $rating = isset($rawPayload['supplier_rating']) || isset($rawPayload['rating'])
            ? (float) ($rawPayload['supplier_rating'] ?? $rawPayload['rating'])
            : null;
        $reviewsCount = (int) ($rawPayload['order_count'] ?? $rawPayload['reviews_count'] ?? 0);
        $url = (string) ($rawPayload['product_url'] ?? $rawPayload['url'] ?? "https://www.alibaba.com/product-detail/{$externalId}.html");
        $imageUrl = $rawPayload['image_url'] ?? $rawPayload['main_image'] ?? null;
        $supplierName = $rawPayload['company_name'] ?? $rawPayload['supplier_name'] ?? 'Alibaba Verified Supplier';

        return new ExternalProductDto(
            source: 'alibaba',
            externalId: $externalId,
            title: $title,
            price: $price,
            currency: strtoupper((string) ($rawPayload['currency'] ?? 'USD')),
            url: $url,
            description: $rawPayload['description'] ?? null,
            brand: $rawPayload['brand'] ?? $supplierName,
            originalPrice: null,
            rating: $rating,
            reviewsCount: $reviewsCount,
            supplierName: $supplierName,
            minOrderQuantity: $minOrder > 0 ? $minOrder : 1,
            imageUrl: $imageUrl,
            isAvailable: true,
            categorySlug: $rawPayload['category_slug'] ?? null,
            rawPayload: $rawPayload
        );
    }
}

