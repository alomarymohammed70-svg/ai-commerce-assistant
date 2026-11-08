<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Consumer electronics, gadgets, and accessories',
                'children' => [
                    ['name' => 'Audio & Headphones', 'slug' => 'audio-headphones'],
                    ['name' => 'Smart Home Gadgets', 'slug' => 'smart-home'],
                    ['name' => 'Mobile Phone Accessories', 'slug' => 'mobile-accessories'],
                ],
            ],
            [
                'name' => 'Fashion & Apparel',
                'slug' => 'fashion',
                'description' => 'Clothing, footwear, and wearable goods',
                'children' => [
                    ['name' => 'Men Fashion', 'slug' => 'men-fashion'],
                    ['name' => 'Women Fashion', 'slug' => 'women-fashion'],
                ],
            ],
            [
                'name' => 'Home & Kitchen',
                'slug' => 'home-kitchen',
                'description' => 'Kitchen appliances, dining, and decor',
                'children' => [
                    ['name' => 'Kitchen Dining', 'slug' => 'kitchen-dining'],
                    ['name' => 'Home Decor', 'slug' => 'home-decor'],
                ],
            ],
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty',
                'description' => 'Skincare, haircare, and personal grooming',
                'children' => [],
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sporting equipment and fitness gear',
                'children' => [],
            ],
        ];

        foreach ($categories as $cat) {
            $parent = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );

            foreach ($cat['children'] as $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'description' => null,
                        'parent_id' => $parent->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

