<?php

namespace App\Services\ExternalSources\Connectors;

use App\Contracts\ExternalSources\ExternalSourceConnectorInterface;
use App\DTOs\ExternalSources\ExternalProductDto;

/**
 * Mock external connector for testing the pipeline end-to-end without real HTTP requests or external API credentials.
 * Simulates external marketplace data from Amazon, Alibaba, eBay, etc.
 */
class MockExternalSourceConnector implements ExternalSourceConnectorInterface
{
    public function __construct(
        protected string $sourceIdentifier = 'mock-amazon',
        protected ?string $defaultCurrency = 'USD'
    ) {}

    public function getSourceIdentifier(): string
    {
        return $this->sourceIdentifier;
    }

    public function search(string $query, array $parameters = []): array
    {
        $normalizedQuery = strtolower(trim($query));
        $items = [];

        // Realistic mock catalog
        $catalog = [
            [
                'external_id' => 'MOCK-' . strtoupper($this->sourceIdentifier) . '-101',
                'title' => 'Sony WH-1000XM4 Wireless Noise Cancelling Headphones - Black',
                'brand' => 'Sony',
                'price' => $this->sourceIdentifier === 'alibaba' ? 180.00 : 348.00,
                'currency' => $this->sourceIdentifier === 'alibaba' ? 'USD' : ($this->defaultCurrency ?: 'USD'),
                'original_price' => 399.99,
                'rating' => 4.7,
                'reviews_count' => 12500,
                'url' => 'https://' . $this->sourceIdentifier . '.example.com/product/101',
                'is_available' => true,
            ],
            [
                'external_id' => 'MOCK-' . strtoupper($this->sourceIdentifier) . '-102',
                'title' => 'Anker 737 Power Bank PowerCore 24K 140W Output 24000mAh',
                'brand' => 'Anker',
                'price' => $this->sourceIdentifier === 'alibaba' ? 65.00 : 149.99,
                'currency' => $this->defaultCurrency ?: 'USD',
                'original_price' => 169.99,
                'rating' => 4.6,
                'reviews_count' => 8400,
                'url' => 'https://' . $this->sourceIdentifier . '.example.com/product/102',
                'is_available' => true,
            ],
            [
                'external_id' => 'MOCK-' . strtoupper($this->sourceIdentifier) . '-103',
                'title' => 'Apple AirPods Pro 2nd Generation with MagSafe Case',
                'brand' => 'Apple',
                'price' => 249.00,
                'currency' => $this->defaultCurrency ?: 'USD',
                'original_price' => 249.00,
                'rating' => 4.8,
                'reviews_count' => 34000,
                'url' => 'https://' . $this->sourceIdentifier . '.example.com/product/103',
                'is_available' => true,
            ],
        ];

        foreach ($catalog as $item) {
            if (empty($normalizedQuery) || str_contains(strtolower($item['title']), $normalizedQuery) || str_contains(strtolower($item['brand']), $normalizedQuery)) {
                $items[] = new ExternalProductDto(
                    source: $this->sourceIdentifier,
                    externalId: $item['external_id'],
                    title: $item['title'],
                    description: 'Mock catalog item description for ' . $item['title'],
                    brand: $item['brand'],
                    url: $item['url'],
                    imageUrl: 'https://via.placeholder.com/640x480.png?text=' . urlencode($item['brand']),
                    currency: $item['currency'],
                    price: (float) $item['price'],
                    originalPrice: (float) $item['original_price'],
                    rating: (float) $item['rating'],
                    reviewsCount: (int) $item['reviews_count'],
                    isAvailable: (bool) $item['is_available'],
                    categorySlug: 'electronics',
                    rawPayload: [
                        'mock' => true,
                        'source' => $this->sourceIdentifier,
                        'query' => $query,
                        'raw_item' => $item,
                    ]
                );
            }
        }

        return $items;
    }

    public function fetchDetails(string $externalProductId): ?ExternalProductDto
    {
        return new ExternalProductDto(
            source: $this->sourceIdentifier,
            externalId: $externalProductId,
            title: 'Mock Product ' . $externalProductId,
            description: 'Mock product fetched by ID for testing pipeline',
            brand: 'MockBrand',
            url: 'https://' . $this->sourceIdentifier . '.example.com/item/' . $externalProductId,
            imageUrl: 'https://via.placeholder.com/640x480.png?text=Mock',
            currency: $this->defaultCurrency ?: 'USD',
            price: 99.99,
            originalPrice: 129.99,
            rating: 4.5,
            reviewsCount: 150,
            isAvailable: true,
            categorySlug: 'electronics',
            rawPayload: [
                'mock' => true,
                'external_id' => $externalProductId,
                'source' => $this->sourceIdentifier,
            ]
        );
    }

    public function checkHealth(): bool
    {
        return true;
    }
}

