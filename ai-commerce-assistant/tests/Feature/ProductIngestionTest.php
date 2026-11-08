<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSource;
use App\Services\Product\ProductIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected ProductIngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductIngestionService::class);
    }

    public function test_ingest_from_raw_amazon_payload(): void
    {
        $category = Category::factory()->create(['slug' => 'electronics']);

        $rawAmazon = [
            'asin' => 'B09V3KXK7G',
            'product_title' => 'Anker Soundcore Space Q45 Headphones',
            'buying_price' => 149.99,
            'list_price' => 179.99,
            'stars' => 4.6,
            'ratings_total' => 6200,
            'detail_page_url' => 'https://www.amazon.com/dp/B09V3KXK7G',
            'manufacturer' => 'Anker',
            'category_slug' => 'electronics',
        ];

        $product = $this->service->ingestFromRaw('amazon', $rawAmazon);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('B09V3KXK7G', $product->external_id);
        $this->assertEquals('Anker Soundcore Space Q45 Headphones', $product->title);
        $this->assertEquals('149.9900', $product->price);
        $this->assertEquals('179.9900', $product->original_price);
        $this->assertEquals('4.60', $product->rating);
        $this->assertEquals(6200, $product->reviews_count);
        $this->assertEquals('Anker', $product->brand);
        $this->assertEquals($category->id, $product->category_id);

        $this->assertDatabaseHas('product_sources', [
            'code' => 'amazon',
        ]);
        $this->assertDatabaseHas('products', [
            'external_id' => 'B09V3KXK7G',
        ]);
    }

    public function test_ingestion_is_idempotent_and_updates_price(): void
    {
        $rawItem = [
            'asin' => 'B00SAMPLE123',
            'title' => 'Sample Product',
            'price' => 20.00,
        ];

        $product1 = $this->service->ingestFromRaw('amazon', $rawItem);
        $this->assertEquals('20.0000', $product1->price);

        // Price drops to 15.00
        $rawItem['price'] = 15.00;
        $product2 = $this->service->ingestFromRaw('amazon', $rawItem);

        // Same record in DB, updated price
        $this->assertEquals($product1->id, $product2->id);
        $this->assertEquals('15.0000', $product2->fresh()->price);
        $this->assertEquals(1, Product::where('external_id', 'B00SAMPLE123')->count());
    }

    public function test_ingest_throws_exception_if_external_id_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->ingestFromRaw('amazon', [
            'title' => 'Invalid Product No ID',
            'price' => 10.00,
        ]);
    }
}

