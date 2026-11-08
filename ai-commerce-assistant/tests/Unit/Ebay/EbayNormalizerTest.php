<?php

namespace Tests\Unit\Ebay;

use App\Services\ExternalSources\Normalizers\EbayProductNormalizer;
use Tests\TestCase;

class EbayNormalizerTest extends TestCase
{
    protected EbayProductNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new EbayProductNormalizer();
    }

    public function test_normalizes_ebay_browse_item_summary_correctly(): void
    {
        $rawSummary = [
            'itemId' => 'v1|1155998844|0',
            'title' => 'HP OfficeJet Pro 9015e Wireless Color All-in-One Printer',
            'price' => [
                'value' => '229.99',
                'currency' => 'USD',
            ],
            'image' => [
                'imageUrl' => 'https://i.ebayimg.com/images/g/xyz/s-l500.jpg',
            ],
            'itemWebUrl' => 'https://www.ebay.com/itm/1155998844',
            'condition' => 'New',
            'seller' => [
                'username' => 'official_hp_store',
                'feedbackPercentage' => '99.6',
                'feedbackScore' => 14850,
            ],
            'categories' => [
                [
                    'categoryId' => '171961',
                    'categoryName' => 'Printers-Scanners',
                ],
            ],
        ];

        $dto = $this->normalizer->normalize($rawSummary);

        $this->assertEquals('ebay', $dto->source);
        $this->assertEquals('v1|1155998844|0', $dto->externalId);
        $this->assertEquals('HP OfficeJet Pro 9015e Wireless Color All-in-One Printer', $dto->title);
        $this->assertEquals(229.99, $dto->price);
        $this->assertEquals('USD', $dto->currency);
        $this->assertEquals('https://www.ebay.com/itm/1155998844', $dto->url);
        $this->assertEquals('https://i.ebayimg.com/images/g/xyz/s-l500.jpg', $dto->imageUrl);
        $this->assertNull($dto->rating);
        $this->assertEquals(0, $dto->reviewsCount);
        $this->assertEquals(99.6, $dto->rawPayload['seller_feedback_percentage']);
        $this->assertEquals(14850, $dto->rawPayload['seller_feedback_score']);
        $this->assertEquals('official_hp_store', $dto->supplierName);
        $this->assertTrue($dto->isAvailable);
        $this->assertEquals('printers-scanners', $dto->categorySlug);
    }

    public function test_normalizes_detailed_item_with_brand_and_availability(): void
    {
        $rawDetail = [
            'itemId' => 'v1|99887766|0',
            'title' => 'Canon Color ImageCLASS MF644Cdw All-in-One Wireless Laser Printer',
            'brand' => 'Canon',
            'description' => '<p>High quality multifunction printer</p>',
            'price' => [
                'value' => '349.00',
                'currency' => 'USD',
            ],
            'itemWebUrl' => 'https://www.ebay.com/itm/99887766',
            'estimatedAvailabilities' => [
                [
                    'estimatedAvailabilityStatus' => 'IN_STOCK',
                    'estimatedAvailableQuantity' => 10,
                ],
            ],
        ];

        $dto = $this->normalizer->normalize($rawDetail);

        $this->assertEquals('v1|99887766|0', $dto->externalId);
        $this->assertEquals('Canon', $dto->brand);
        $this->assertEquals('<p>High quality multifunction printer</p>', $dto->description);
        $this->assertEquals(349.00, $dto->price);
        $this->assertTrue($dto->isAvailable);
    }

    public function test_missing_fields_yield_null_without_hallucinating_values(): void
    {
        $sparse = [
            'itemId' => 'v1|sparse|0',
            'title' => 'Minimal Item',
        ];

        $dto = $this->normalizer->normalize($sparse);

        $this->assertEquals('v1|sparse|0', $dto->externalId);
        $this->assertEquals('Minimal Item', $dto->title);
        $this->assertEquals(0.0, $dto->price);
        $this->assertEquals('USD', $dto->currency);
        $this->assertNull($dto->brand);
        $this->assertNull($dto->description);
        $this->assertNull($dto->rating);
        $this->assertEquals(0, $dto->reviewsCount);
    }

    public function test_normalizes_explicit_product_review_metrics(): void
    {
        $rawWithReviews = [
            'itemId' => 'v1|999|0',
            'title' => 'Sample Product with Explicit Catalog Reviews',
            'reviewRating' => '4.65',
            'reviewCount' => 84,
        ];

        $dto = $this->normalizer->normalize($rawWithReviews);

        $this->assertEquals(4.65, $dto->rating);
        $this->assertEquals(84, $dto->reviewsCount);
    }
}

