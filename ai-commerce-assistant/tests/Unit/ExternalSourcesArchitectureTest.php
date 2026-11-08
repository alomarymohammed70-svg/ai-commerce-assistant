<?php

namespace Tests\Unit;

use App\Contracts\ExternalSources\ExternalSourceConnectorInterface;
use App\DTOs\AI\MarketIntelligenceDto;
use App\DTOs\ExternalSources\ExternalProductDto;
use App\Services\ExternalSources\ExternalSourceManager;
use PHPUnit\Framework\TestCase;

class ExternalSourcesArchitectureTest extends TestCase
{
    public function test_external_product_dto_serialization(): void
    {
        $dto = new ExternalProductDto(
            source: 'amazon',
            externalId: 'B08N5WRWNW',
            title: 'Wireless Earbuds',
            price: 29.99,
            currency: 'USD',
            url: 'https://example.com/item',
            rating: 4.5,
            reviewsCount: 1200,
            supplierName: 'AudioTech',
            minOrderQuantity: 1,
            imageUrl: 'https://example.com/img.jpg',
            rawPayload: ['mock' => true]
        );

        $array = $dto->toArray();

        $this->assertEquals('amazon', $array['source']);
        $this->assertEquals('B08N5WRWNW', $array['external_id']);
        $this->assertEquals(29.99, $array['price']);

        $restoredDto = ExternalProductDto::fromArray($array);
        $this->assertEquals($dto->externalId, $restoredDto->externalId);
        $this->assertEquals($dto->price, $restoredDto->price);
    }

    public function test_external_source_manager_registration(): void
    {
        $manager = new ExternalSourceManager;

        $mockConnector = $this->createMock(ExternalSourceConnectorInterface::class);
        $mockConnector->method('getSourceIdentifier')->willReturn('aliexpress');

        $manager->registerConnector($mockConnector);

        $this->assertTrue($manager->hasConnector('aliexpress'));
        $this->assertFalse($manager->hasConnector('ebay'));
        $this->assertSame($mockConnector, $manager->connector('aliexpress'));
    }

    public function test_market_intelligence_dto_serialization(): void
    {
        $dto = new MarketIntelligenceDto(
            productId: 'EXT-1234',
            demandScore: 88.5,
            competitionScore: 42.0,
            estimatedProfitMargin: 45.0,
            riskLevel: 'low',
            aiRecommendation: 'High profit margin with moderate competition.',
            suggestedSellingChannels: ['Amazon FBA', 'Shopify'],
            marketMetrics: ['monthly_searches' => 25000]
        );

        $array = $dto->toArray();

        $this->assertEquals('EXT-1234', $array['product_id']);
        $this->assertEquals(88.5, $array['demand_score']);
        $this->assertEquals('low', $array['risk_level']);
        $this->assertContains('Amazon FBA', $array['suggested_selling_channels']);
    }
}

