<?php

namespace Database\Factories;

use App\Models\ProductSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductSource>
 */
class ProductSourceFactory extends Factory
{
    protected $model = ProductSource::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'code' => Str::slug($name),
            'name' => $name,
            'base_url' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}

