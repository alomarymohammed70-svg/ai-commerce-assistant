<?php

namespace Database\Factories;

use App\Models\CanonicalProduct;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CanonicalProduct>
 */
class CanonicalProductFactory extends Factory
{
    protected $model = CanonicalProduct::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true) . ' ' . $this->faker->randomElement(['Pro', 'Max', 'Ultra', 'Plus']);
        $baseSlug = Str::slug($name);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => $baseSlug . '-' . Str::lower(Str::random(6)),
            'brand' => $this->faker->company(),
            'model_number' => strtoupper($this->faker->bothify('MDL-####-??')),
            'description' => $this->faker->paragraph(),
            'base_currency' => 'USD',
        ];
    }
}

