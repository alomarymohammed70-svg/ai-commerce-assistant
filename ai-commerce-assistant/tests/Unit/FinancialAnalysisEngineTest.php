<?php

namespace Tests\Unit;

use App\Services\Analysis\ProductAnalysisEngine;
use PHPUnit\Framework\TestCase;

class FinancialAnalysisEngineTest extends TestCase
{
    protected ProductAnalysisEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ProductAnalysisEngine;
    }

    public function test_standard_profitable_calculation(): void
    {
        $result = $this->engine->calculate([
            'purchase_price' => '10.0000',
            'selling_price' => '30.0000',
            'shipping_cost' => '3.5000',
            'estimated_fees' => '4.5000',
            'other_costs' => '1.0000',
            'rating' => 4.6,
            'reviews_count' => 350,
        ]);

        // Total Cost = 10 + 3.5 + 4.5 + 1 = 19.0000
        $this->assertEquals('19.0000', $result['total_cost']);

        // Profit = 30 - 19 = 11.0000
        $this->assertEquals('11.0000', $result['estimated_profit']);

        // Margin = (11 / 30) * 100 = 36.6666 %
        $this->assertEquals('36.6666', $result['profit_margin']);

        // ROI = (11 / 19) * 100 = 57.8947 %
        $this->assertEquals('57.8947', $result['return_on_investment']);

        $this->assertTrue($result['is_profitable']);
        $this->assertEquals('medium', $result['competition_level']);
        $this->assertGreaterThan(50.0, (float) $result['opportunity_score']);
        $this->assertLessThan(40.0, (float) $result['risk_score']);
    }

    public function test_division_by_zero_safety(): void
    {
        // Selling price is zero: should not throw division by zero error
        $result = $this->engine->calculate([
            'purchase_price' => '10.0000',
            'selling_price' => '0.0000',
            'shipping_cost' => '2.0000',
            'estimated_fees' => '0.0000',
            'other_costs' => '0.0000',
        ]);

        $this->assertEquals('0.0000', $result['profit_margin']);
        $this->assertEquals('-12.0000', $result['estimated_profit']);
        $this->assertFalse($result['is_profitable']);

        // Both selling price and total cost are zero
        $zeroCostResult = $this->engine->calculate([
            'purchase_price' => '0.0000',
            'selling_price' => '0.0000',
            'shipping_cost' => '0.0000',
        ]);

        $this->assertEquals('0.0000', $zeroCostResult['profit_margin']);
        $this->assertEquals('0.0000', $zeroCostResult['return_on_investment']);
    }

    public function test_negative_profit_and_loss_scenario(): void
    {
        $result = $this->engine->calculate([
            'purchase_price' => '25.0000',
            'selling_price' => '20.0000',
            'shipping_cost' => '5.0000',
            'estimated_fees' => '3.0000',
            'rating' => 2.8,
            'reviews_count' => 4,
        ]);

        // Total Cost = 25 + 5 + 3 = 33.0000
        $this->assertEquals('33.0000', $result['total_cost']);

        // Profit = 20 - 33 = -13.0000
        $this->assertEquals('-13.0000', $result['estimated_profit']);

        // Margin = (-13 / 20) * 100 = -65.0000 %
        $this->assertEquals('-65.0000', $result['profit_margin']);

        $this->assertFalse($result['is_profitable']);
        $this->assertEquals('low', $result['competition_level']);

        // Low margin & low rating should escalate risk score
        $this->assertGreaterThan(70.0, (float) $result['risk_score']);
        $this->assertLessThan(30.0, (float) $result['opportunity_score']);
    }

    public function test_missing_values_and_defaults(): void
    {
        // Minimal inputs: fees, shipping, other costs missing
        $result = $this->engine->calculate([
            'purchase_price' => '15.5000',
            'selling_price' => '45.0000',
        ]);

        $this->assertEquals('15.5000', $result['total_cost']);
        $this->assertEquals('29.5000', $result['estimated_profit']);
        $this->assertTrue($result['is_profitable']);
        $this->assertEquals('low', $result['competition_level']);
    }

    public function test_financial_precision_bcmath(): void
    {
        // Fractional unit cent precision (common in Alibaba bulk supplies)
        $result = $this->engine->calculate([
            'purchase_price' => '0.1250',
            'selling_price' => '1.9900',
            'shipping_cost' => '0.2500',
            'estimated_fees' => '0.3000',
            'other_costs' => '0.0500',
        ]);

        // Total cost: 0.1250 + 0.2500 + 0.3000 + 0.0500 = 0.7250
        $this->assertEquals('0.7250', $result['total_cost']);

        // Net profit: 1.9900 - 0.7250 = 1.2650
        $this->assertEquals('1.2650', $result['estimated_profit']);
    }

    public function test_score_boundaries_clamp_between_zero_and_hundred(): void
    {
        // Extreme loss
        $loss = $this->engine->calculate([
            'purchase_price' => '1000.0000',
            'selling_price' => '1.0000',
            'rating' => 1.0,
            'reviews_count' => 1,
        ]);

        $this->assertGreaterThanOrEqual(0.0, (float) $loss['opportunity_score']);
        $this->assertLessThanOrEqual(100.0, (float) $loss['opportunity_score']);
        $this->assertGreaterThanOrEqual(0.0, (float) $loss['risk_score']);
        $this->assertLessThanOrEqual(100.0, (float) $loss['risk_score']);

        // Extreme windfall
        $windfall = $this->engine->calculate([
            'purchase_price' => '1.0000',
            'selling_price' => '1000.0000',
            'rating' => 5.0,
            'reviews_count' => 10000,
        ]);

        $this->assertGreaterThanOrEqual(0.0, (float) $windfall['opportunity_score']);
        $this->assertLessThanOrEqual(100.0, (float) $windfall['opportunity_score']);
        $this->assertGreaterThanOrEqual(0.0, (float) $windfall['risk_score']);
        $this->assertLessThanOrEqual(100.0, (float) $windfall['risk_score']);
    }
}

