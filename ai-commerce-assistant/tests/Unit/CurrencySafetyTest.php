<?php

namespace Tests\Unit;

use App\Services\Analysis\ProductAnalysisEngine;
use App\Services\Currency\StaticCurrencyConverter;
use InvalidArgumentException;
use Tests\TestCase;

class CurrencySafetyTest extends TestCase
{
    protected StaticCurrencyConverter $converter;
    protected ProductAnalysisEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new StaticCurrencyConverter();
        $this->engine = new ProductAnalysisEngine($this->converter);
    }

    public function test_usd_to_usd_conversion_is_identity(): void
    {
        $converted = $this->converter->convert(100.00, 'USD', 'USD');
        $this->assertEquals('100.0000', $converted);
    }

    public function test_cny_to_usd_conversion_accuracy(): void
    {
        // 1 USD = 7.24 CNY -> 100 CNY / 7.24 = 13.812154... -> 13.8122 USD rounded
        $converted = $this->converter->convert(100.00, 'CNY', 'USD');
        $this->assertEquals('13.8122', $converted);
    }

    public function test_eur_to_usd_conversion_accuracy(): void
    {
        // 1 USD = 0.925 EUR -> 100 EUR / 0.925 = 108.1081 USD
        $converted = $this->converter->convert(100.00, 'EUR', 'USD');
        $this->assertEquals('108.1081', $converted);
    }

    public function test_mixed_currency_safety_prevents_silent_raw_subtraction(): void
    {
        // Scenario from Phase 2 audit:
        // Purchase price: 100 CNY
        // Selling price: 30 USD
        // Naive calculation (30 - 100) = -70 (WRONG & catastrophic)
        // Safe normalized calculation:
        // 100 CNY normalized to USD = 13.8122 USD
        // Net profit: 30.0000 - 13.8122 = 16.1878 USD (PROFITABLE!)
        $result = $this->engine->calculate([
            'purchase_price' => 100.00,
            'purchase_currency' => 'CNY',
            'selling_price' => 30.00,
            'selling_currency' => 'USD',
            'currency' => 'USD',
            'shipping_cost' => 0.0,
            'estimated_fees' => 0.0,
        ]);

        $this->assertTrue($result['is_profitable']);
        $this->assertEquals('13.8122', $result['purchase_price']);
        $this->assertEquals('100.0000', $result['original_purchase_price']);
        $this->assertEquals('CNY', $result['original_purchase_currency']);
        $this->assertEquals('30.0000', $result['selling_price']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals('16.1878', $result['estimated_profit']);
        $this->assertEquals('53.9593', $result['profit_margin']);
    }

    public function test_unsupported_currency_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->converter->convert(100.00, 'XYZ_UNKNOWN', 'USD');
    }
}
