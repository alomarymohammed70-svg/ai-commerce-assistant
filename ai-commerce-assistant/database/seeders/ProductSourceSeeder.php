<?php

namespace Database\Seeders;

use App\Models\ProductSource;
use Illuminate\Database\Seeder;

class ProductSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'code' => 'amazon',
                'name' => 'Amazon Marketplace',
                'base_url' => 'https://www.amazon.com',
                'is_active' => true,
            ],
            [
                'code' => 'alibaba',
                'name' => 'Alibaba Wholesale',
                'base_url' => 'https://www.alibaba.com',
                'is_active' => true,
            ],
            [
                'code' => 'aliexpress',
                'name' => 'AliExpress Global',
                'base_url' => 'https://www.aliexpress.com',
                'is_active' => true,
            ],
            [
                'code' => 'ebay',
                'name' => 'eBay Global Marketplace',
                'base_url' => 'https://www.ebay.com',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            ProductSource::updateOrCreate(
                ['code' => $source['code']],
                $source
            );
        }
    }
}

