<?php

namespace Database\Seeders;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MultiMarketProductSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::where('slug', 'electronics')->first()
            ?? Category::firstOrCreate(['name' => 'Electronics', 'slug' => 'electronics']);

        $amazon = ProductSource::where('code', 'amazon')->first()
            ?? ProductSource::create(['code' => 'amazon', 'name' => 'Amazon Marketplace', 'is_active' => true]);

        $alibaba = ProductSource::where('code', 'alibaba')->first()
            ?? ProductSource::create(['code' => 'alibaba', 'name' => 'Alibaba Wholesale', 'is_active' => true]);

        $ebay = ProductSource::where('code', 'ebay')->first()
            ?? ProductSource::create(['code' => 'ebay', 'name' => 'eBay Marketplace', 'is_active' => true]);

        $aliexpress = ProductSource::where('code', 'aliexpress')->first()
            ?? ProductSource::create(['code' => 'aliexpress', 'name' => 'AliExpress Global', 'is_active' => true]);

        // 1. Sony WH-1000XM4
        $sony = CanonicalProduct::firstOrCreate(
            ['slug' => 'sony-wh-1000xm4-wireless-headphones'],
            [
                'category_id' => $electronics->id,
                'name' => 'Sony WH-1000XM4 Wireless Headphones',
                'brand' => 'Sony',
                'model_number' => 'WH-1000XM4',
                'description' => 'Industry-leading noise canceling overhead Bluetooth headphones with microphone.',
                'base_currency' => 'USD',
            ]
        );

        ProductListing::updateOrCreate(
            ['source_id' => $amazon->id, 'external_id' => 'B0863TXGM3'],
            [
                'canonical_product_id' => $sony->id,
                'category_id' => $electronics->id,
                'title' => 'Sony WH-1000XM4 Wireless Industry Leading Noise Canceling Overhead Headphones - Black',
                'description' => 'Amazon offer with fast prime delivery.',
                'brand' => 'Sony',
                'url' => 'https://www.amazon.com/dp/B0863TXGM3',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Sony+Amazon',
                'currency' => 'USD',
                'price' => 348.0000,
                'original_price' => 399.9900,
                'rating' => 4.70,
                'reviews_count' => 54200,
                'is_available' => true,
                'observed_at' => now(),
            ]
        );

        ProductListing::updateOrCreate(
            ['source_id' => $alibaba->id, 'external_id' => 'ALB-SONY-991'],
            [
                'canonical_product_id' => $sony->id,
                'category_id' => $electronics->id,
                'title' => 'Original Sony WH-1000XM4 ANC Wireless Headphones Factory Wholesale Offer',
                'description' => 'Wholesale supplier on Alibaba with MOQs.',
                'brand' => 'Sony',
                'url' => 'https://www.alibaba.com/product-detail/ALB-SONY-991.html',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Sony+Alibaba',
                'currency' => 'USD',
                'price' => 180.0000,
                'original_price' => 220.0000,
                'rating' => 4.60,
                'reviews_count' => 320,
                'is_available' => true,
                'observed_at' => now(),
            ]
        );

        ProductListing::updateOrCreate(
            ['source_id' => $ebay->id, 'external_id' => 'EBAY-SONY-772'],
            [
                'canonical_product_id' => $sony->id,
                'category_id' => $electronics->id,
                'title' => 'Sony WH-1000XM4 Wireless Noise Cancelling Over-Ear Headphones - Sealed New',
                'description' => 'Authorized refurbished and brand new eBay listings.',
                'brand' => 'Sony',
                'url' => 'https://www.ebay.com/itm/EBAY-SONY-772',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Sony+eBay',
                'currency' => 'USD',
                'price' => 310.0000,
                'original_price' => 349.9900,
                'rating' => 4.80,
                'reviews_count' => 1420,
                'is_available' => true,
                'observed_at' => now(),
            ]
        );

        // 2. Anker 737 Power Bank
        $anker = CanonicalProduct::firstOrCreate(
            ['slug' => 'anker-737-power-bank-24000mah'],
            [
                'category_id' => $electronics->id,
                'name' => 'Anker 737 Power Bank (PowerCore 24K)',
                'brand' => 'Anker',
                'model_number' => 'A1289',
                'description' => 'High capacity 24,000mAh portable charger with 140W bi-directional smart display.',
                'base_currency' => 'USD',
            ]
        );

        ProductListing::updateOrCreate(
            ['source_id' => $amazon->id, 'external_id' => 'B09V3KXK7G'],
            [
                'canonical_product_id' => $anker->id,
                'category_id' => $electronics->id,
                'title' => 'Anker 737 Power Bank, 24,000mAh 3-Port Laptop Portable Charger 140W Output',
                'description' => 'Amazon prime certified.',
                'brand' => 'Anker',
                'url' => 'https://www.amazon.com/dp/B09V3KXK7G',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Anker+Amazon',
                'currency' => 'USD',
                'price' => 149.9900,
                'original_price' => 179.9900,
                'rating' => 4.60,
                'reviews_count' => 8400,
                'is_available' => true,
                'observed_at' => now(),
            ]
        );

        ProductListing::updateOrCreate(
            ['source_id' => $aliexpress->id, 'external_id' => 'ALI-ANKER-448'],
            [
                'canonical_product_id' => $anker->id,
                'category_id' => $electronics->id,
                'title' => 'Anker 737 Power Bank PowerCore 24K 140W Fast Charging Official Store',
                'description' => 'AliExpress global delivery.',
                'brand' => 'Anker',
                'url' => 'https://www.aliexpress.com/item/ALI-ANKER-448.html',
                'image_url' => 'https://via.placeholder.com/640x480.png?text=Anker+AliExpress',
                'currency' => 'USD',
                'price' => 99.5000,
                'original_price' => 129.0000,
                'rating' => 4.70,
                'reviews_count' => 1890,
                'is_available' => true,
                'observed_at' => now(),
            ]
        );
    }
}

