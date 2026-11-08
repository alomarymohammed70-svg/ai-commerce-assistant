<?php

namespace Database\Factories;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductListing>
 */
class ProductListingFactory extends Factory
{
    protected $model = ProductListing::class;

    public function definition(): array
    {
        $price = $this->faker->randomFloat(4, 5, 250);
        $hasOriginal = $this->faker->boolean(60);

        return [
            'canonical_product_id' => CanonicalProduct::factory(),
            'source_id' => ProductSource::factory(),
            'category_id' => Category::factory(),
            'external_id' => strtoupper($this->faker->bothify('EXT-????-####')),
            'title' => $this->faker->sentence(4),
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

