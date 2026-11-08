<?php

namespace Tests\Unit;

use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Services\Comparison\PriceComparisonService;
use App\Services\Currency\StaticCurrencyConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected PriceComparisonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceComparisonService(new StaticCurrencyConverter());
    }

    public function test_price_comparison_across_multiple_marketplace_listings(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Sony WH-1000XM4 Wireless Headphones',
            'base_currency' => 'USD',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon', 'name' => 'Amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba', 'name' => 'Alibaba']);
        $ebay = ProductSource::factory()->create(['code' => 'ebay', 'name' => 'eBay']);

        // Amazon Listing: 348.00 USD
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 348.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // Alibaba Listing: 180.00 USD
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 180.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // eBay Listing: 310.00 USD
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $ebay->id,
            'price' => 310.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        $result = $this->service->compare($canonical, 'USD');

        $this->assertEquals(3, $result->totalOffersCount);
        $this->assertNotNull($result->lowestOffer);
        $this->assertEquals('alibaba', $result->lowestOffer->sourceCode);
        $this->assertEquals('180.0000', $result->lowestOffer->normalizedPrice);

        $this->assertNotNull($result->highestOffer);
        $this->assertEquals('amazon', $result->highestOffer->sourceCode);
        $this->assertEquals('348.0000', $result->highestOffer->normalizedPrice);

        // Price Spread: 348 - 180 = 168.0000
        $this->assertEquals('168.0000', $result->priceSpread);
        // Price Spread %: (168 / 180) * 100 = 93.33%
        $this->assertEquals(93.33, $result->priceSpreadPercentage);
        // Potential Gross Margin %: (168 / 348) * 100 = 48.28%
        $this->assertEquals(48.28, $result->potentialGrossMarginPercentage);
        // Average Price: (348 + 180 + 310) / 3 = 838 / 3 = 279.3333
        $this->assertEquals('279.3333', $result->averagePrice);
    }

    public function test_price_comparison_with_multi_currency_normalization(): void
    {
        $canonical = CanonicalProduct::factory()->create([
            'name' => 'Multi-Currency Product',
            'base_currency' => 'USD',
        ]);

        $amazon = ProductSource::factory()->create(['code' => 'amazon']);
        $alibaba = ProductSource::factory()->create(['code' => 'alibaba']);

        // Amazon: 50 USD
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $amazon->id,
            'price' => 50.0000,
            'currency' => 'USD',
            'is_available' => true,
        ]);

        // Alibaba: 144.80 CNY (which is 144.80 / 7.24 = 20.00 USD)
        ProductListing::factory()->create([
            'canonical_product_id' => $canonical->id,
            'source_id' => $alibaba->id,
            'price' => 144.8000,
            'currency' => 'CNY',
            'is_available' => true,
        ]);

        $result = $this->service->compare($canonical, 'USD');

        $this->assertEquals(2, $result->totalOffersCount);
        $this->assertEquals('20.0000', $result->lowestOffer->normalizedPrice);
        $this->assertEquals('CNY', $result->lowestOffer->originalCurrency);
        $this->assertEquals('144.8000', $result->lowestOffer->originalPrice);
        $this->assertEquals('50.0000', $result->highestOffer->normalizedPrice);
        $this->assertEquals('30.0000', $result->priceSpread);
    }

    public function test_price_comparison_with_zero_listings(): void
    {
        $canonical = CanonicalProduct::factory()->create();

        $result = $this->service->compare($canonical, 'USD');

        $this->assertEquals(0, $result->totalOffersCount);
        $this->assertNull($result->lowestOffer);
        $this->assertNull($result->highestOffer);
        $this->assertEquals('0.0000', $result->priceSpread);
        $this->assertEmpty($result->offers);
    }
}

