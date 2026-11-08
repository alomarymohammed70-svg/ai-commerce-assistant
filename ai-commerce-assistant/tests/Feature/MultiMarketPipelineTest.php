<?php

namespace Tests\Feature;

use App\Models\CanonicalProduct;
use App\Models\Category;
use App\Models\ProductListing;
use App\Models\ProductSource;
use App\Services\ExternalSources\Connectors\MockExternalSourceConnector;
use App\Services\Product\ProductIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiMarketPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected ProductIngestionService $ingestionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestionService = app(ProductIngestionService::class);
    }

    public function test_multi_market_offers_link_to_same_canonical_product(): void
    {
        $category = Category::factory()->create(['slug' => 'electronics']);

        // 1. Ingest Amazon Offer
        $amazonPayload = [
            'asin' => 'B0863TXGM3',
            'product_title' => 'Sony WH-1000XM4 Wireless Noise Cancelling Headphones - Black',
            'buying_price' => 348.00,
            'stars' => 4.7,
            'ratings_total' => 54000,
            'manufacturer' => 'Sony',
            'detail_page_url' => 'https://www.amazon.com/dp/B0863TXGM3',
            'category_slug' => 'electronics',
        ];

        $amazonListing = $this->ingestionService->ingestFromRaw('amazon', $amazonPayload);
        $this->assertNotNull($amazonListing->canonical_product_id);

        $canonical = CanonicalProduct::find($amazonListing->canonical_product_id);
        $this->assertNotNull($canonical);
        $this->assertEquals('Sony', $canonical->brand);

        // 2. Ingest Alibaba Offer for the exact same physical product
        $alibabaPayload = [
            'item_id' => 'ALB-SONY-1000',
            'subject' => 'Original Sony WH-1000XM4 Noise Cancelling Headphones Wholesale',
            'price_usd' => 180.00,
            'seller_rating' => 4.6,
            'orders_count' => 320,
            'company_name' => 'Sony',
            'product_url' => 'https://www.alibaba.com/product/1000.html',
            'category_slug' => 'electronics',
        ];

        $alibabaListing = $this->ingestionService->ingestFromRaw('alibaba', $alibabaPayload);

        // Deterministic matcher should link Alibaba offer to the same canonical product!
        $this->assertEquals($canonical->id, $alibabaListing->canonical_product_id);

        // 3. Ingest eBay Offer
        $ebayPayload = [
            'itemId' => 'v1|EBAY-SONY-772|0',
            'title' => 'Sony WH-1000XM4 Noise Cancelling Wireless Headphones Sealed',
            'price' => [
                'value' => '310.00',
                'currency' => 'USD',
            ],
            'itemAffiliateWebUrl' => 'https://www.ebay.com/itm/772',
            'itemLocation' => ['country' => 'US'],
        ];

        $ebayListing = $this->ingestionService->ingestFromRaw('ebay', $ebayPayload);
        $this->assertEquals($canonical->id, $ebayListing->canonical_product_id);

        // Verify the Canonical Product now has exactly 3 listings across distinct sources
        $this->assertEquals(3, $canonical->listings()->count());
        $sources = $canonical->listings()->with('source')->get()->pluck('source.code')->toArray();
        $this->assertContains('amazon', $sources);
        $this->assertContains('alibaba', $sources);
        $this->assertContains('ebay', $sources);
    }

    public function test_listing_ingestion_is_strictly_idempotent(): void
    {
        $payload = [
            'asin' => 'B00IDEMPOTENT',
            'title' => 'Initial Title',
            'price' => 50.00,
        ];

        $first = $this->ingestionService->ingestFromRaw('amazon', $payload);
        $this->assertEquals('50.0000', $first->price);

        // Re-ingest with updated price and title
        $payload['price'] = 45.00;
        $payload['title'] = 'Updated Title';

        $second = $this->ingestionService->ingestFromRaw('amazon', $payload);

        // Must update same record, not create duplicate
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals('45.0000', $second->fresh()->price);
        $this->assertEquals('Updated Title', $second->fresh()->title);

        $amazonSource = ProductSource::where('code', 'amazon')->first();
        $count = ProductListing::where('source_id', $amazonSource->id)
            ->where('external_id', 'B00IDEMPOTENT')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_mock_external_source_connector_searches_and_ingests(): void
    {
        $connector = new MockExternalSourceConnector('mock-amazon');
        $this->assertTrue($connector->checkHealth());

        $results = $connector->search('Sony');
        $this->assertNotEmpty($results);

        $firstDto = $results[0];
        $this->assertStringContainsString('Sony', $firstDto->title);

        $listing = $this->ingestionService->ingestFromDto($firstDto);
        $this->assertNotNull($listing->canonical_product_id);
        $this->assertEquals('Sony', $listing->brand);
        $this->assertDatabaseHas('product_listings', [
            'external_id' => $firstDto->externalId,
        ]);
    }
}

