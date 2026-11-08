<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductMetric>
 */
class ProductMetricFactory extends Factory
{
    protected $model = ProductMetric::class;

    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(4, 5, 50);
        $sellingPrice = round($purchasePrice * 2.2, 4);
        $shipping = $this->faker->randomFloat(4, 2, 8);
        $fees = round($sellingPrice * 0.15, 4);
        $other = 0.0;
        $profit = round($sellingPrice - ($purchasePrice + $shipping + $fees + $other), 4);
        $margin = round(($profit / $sellingPrice) * 100, 4);
        $roi = round(($profit / ($purchasePrice + $shipping + $fees)) * 100, 4);

        return [
            'canonical_product_id' => null,
            'product_id' => Product::factory(),
            'selling_price' => $sellingPrice,
            'purchase_price' => $purchasePrice,
            'shipping_cost' => $shipping,
            'estimated_fees' => $fees,
            'other_costs' => $other,
            'estimated_profit' => $profit,
            'profit_margin' => $margin,
            'return_on_investment' => $roi,
            'opportunity_score' => $this->faker->randomFloat(2, 40, 95),
            'risk_score' => $this->faker->randomFloat(2, 10, 60),
            'competition_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'calculation_notes' => ['is_profitable' => $profit > 0],
            'calculated_at' => now(),
        ];
    }
}
