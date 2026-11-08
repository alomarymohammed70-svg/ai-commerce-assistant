<?php

namespace Database\Factories;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(4, 5, 250);
        $hasOriginal = $this->faker->boolean(60);
        $title = $this->faker->sentence(4);

        return [
            'canonical_product_id' => function (array $attributes) use ($title) {
                return CanonicalProduct::factory()->create([
                    'name' => $attributes['title'] ?? $title,
                    'category_id' => $attributes['category_id'] ?? null,
                    'brand' => $attributes['brand'] ?? null,
                    'base_currency' => $attributes['currency'] ?? 'USD',
                ])->id;
            },
            'source_id' => ProductSource::factory(),
            'category_id' => Category::factory(),
            'external_id' => strtoupper($this->faker->bothify('EXT-????-####')),
            'title' => $title,
            'description' => $this->faker->paragraph(),
            'brand' => $this->faker->company(),
            'url' => $this->faker->url(),
            'image_url' => $this->faker->imageUrl(640, 480, 'products'),
            'currency' => 'USD',
            'price' => $price,
            'original_price' => $hasOriginal ? round($price * 1.3, 4) : null,
            'rating' => $this->faker->randomFloat(2, 3.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(5, 5000),
            'is_available' => true,
            'raw_payload' => ['mock' => true],
            'observed_at' => now(),
        ];
    }
}
