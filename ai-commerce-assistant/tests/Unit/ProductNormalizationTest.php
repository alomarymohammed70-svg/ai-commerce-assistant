<?php

namespace Tests\Unit;

use App\Services\ExternalSources\ProductNormalizationService;
use PHPUnit\Framework\TestCase;

class ProductNormalizationTest extends TestCase
{
    protected ProductNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductNormalizationService;
    }

    public function test_amazon_normalization(): void
    {
        $rawAmazon = [
            'asin' => 'B08N5WRWNW',
            'product_title' => 'Sony WH-1000XM4 Wireless Headphones',
            'buying_price' => 348.00,
            'list_price' => 399.99,
            'stars' => 4.7,
            'ratings_total' => 48200,
            'detail_page_url' => 'https://www.amazon.com/dp/B08N5WRWNW',
            'main_image' => 'https://m.media-amazon.com/images/I/71o8Q5XJS5L.jpg',
            'manufacturer' => 'Sony',
            'currency' => 'USD',
            'in_stock' => true,
        ];

        $dto = $this->service->normalize('amazon', $rawAmazon);

        $this->assertEquals('amazon', $dto->source);
        $this->assertEquals('B08N5WRWNW', $dto->externalId);
        $this->assertEquals('Sony WH-1000XM4 Wireless Headphones', $dto->title);
        $this->assertEquals(348.00, $dto->price);
        $this->assertEquals(399.99, $dto->originalPrice);
        $this->assertEquals(4.7, $dto->rating);
        $this->assertEquals(48200, $dto->reviewsCount);
        $this->assertEquals('Sony', $dto->brand);
        $this->assertTrue($dto->isAvailable);
        $this->assertSame($rawAmazon, $dto->rawPayload);
    }

    public function test_alibaba_normalization(): void
    {
        $rawAlibaba = [
            'product_id' => 'ALB-987654321',
            'subject' => 'OEM TWS Wireless Earbuds Noise Cancelling',
            'fob_price' => 4.25,
            'moq' => 100,
            'company_name' => 'Shenzhen Electronics Co., Ltd',
            'supplier_rating' => 4.8,
            'order_count' => 1250,
            'product_url' => 'https://www.alibaba.com/product-detail/ALB-987654321.html',
            'currency' => 'USD',
        ];

        $dto = $this->service->normalize('alibaba', $rawAlibaba);

        $this->assertEquals('alibaba', $dto->source);
        $this->assertEquals('ALB-987654321', $dto->externalId);
        $this->assertEquals(4.25, $dto->price);
        $this->assertEquals(100, $dto->minOrderQuantity);
        $this->assertEquals('Shenzhen Electronics Co., Ltd', $dto->supplierName);
        $this->assertEquals(4.8, $dto->rating);
        $this->assertEquals(1250, $dto->reviewsCount);
    }

    public function test_aliexpress_normalization(): void
    {
        $rawAliExpress = [
            'item_id' => '1005003456789',
            'title' => 'Mini Portable Bluetooth Speaker Waterproof',
            'target_sale_price' => 12.99,
            'original_price' => 25.99,
            'evaluate_rate' => 96.0, // 96% -> 4.8 / 5
            'order_count' => 3400,
            'store_name' => 'Top Audio Official Store',
            'product_detail_url' => 'https://www.aliexpress.com/item/1005003456789.html',
            'currency' => 'USD',
        ];

        $dto = $this->service->normalize('aliexpress', $rawAliExpress);

        $this->assertEquals('aliexpress', $dto->source);
        $this->assertEquals('1005003456789', $dto->externalId);
        $this->assertEquals(12.99, $dto->price);
        $this->assertEquals(25.99, $dto->originalPrice);
        $this->assertEquals(4.8, $dto->rating);
        $this->assertEquals(3400, $dto->reviewsCount);
        $this->assertEquals('Top Audio Official Store', $dto->supplierName);
    }

    public function test_ebay_normalization(): void
    {
        $rawEbay = [
            'item_id' => '224567891234',
            'title' => 'Vintage Mechanical Keyboard Blue Switches',
            'price' => 45.00,
            'view_item_url' => 'https://www.ebay.com/itm/224567891234',
            'positive_feedback_percent' => 99.4, // -> 4.97
            'feedback_score' => 850,
            'seller_username' => 'RetroTechHub',
            'currency' => 'USD',
        ];

        $dto = $this->service->normalize('ebay', $rawEbay);

        $this->assertEquals('ebay', $dto->source);
        $this->assertEquals('224567891234', $dto->externalId);
        $this->assertEquals(45.00, $dto->price);
        // Audited in Phase 4: seller feedback is strictly decoupled from product rating/reviews
        $this->assertNull($dto->rating);
        $this->assertEquals(0, $dto->reviewsCount);
        $this->assertEquals(99.4, $dto->rawPayload['seller_feedback_percentage']);
        $this->assertEquals(850, $dto->rawPayload['seller_feedback_score']);
    }

    public function test_fallback_to_generic_for_unknown_source(): void
    {
        $rawUnknown = [
            'source' => 'custom_mart',
            'external_id' => 'CM-100',
            'title' => 'Custom Product',
            'price' => 19.99,
            'currency' => 'USD',
            'url' => 'https://example.com/cm-100',
        ];

        $dto = $this->service->normalize('custom_mart', $rawUnknown);

        $this->assertEquals('custom_mart', $dto->source);
        $this->assertEquals('CM-100', $dto->externalId);
        $this->assertEquals(19.99, $dto->price);
    }
}

