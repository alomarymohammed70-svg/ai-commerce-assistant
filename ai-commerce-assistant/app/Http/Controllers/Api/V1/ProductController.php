<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Comparison\PriceComparisonServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\AnalyzeProductRequest;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Http\Resources\Api\V1\CanonicalProductDetailResource;
use App\Http\Resources\Api\V1\CanonicalProductResource;
use App\Http\Resources\Api\V1\PriceComparisonResource;
use App\Http\Resources\Api\V1\ProductListingResource;
use App\Http\Resources\Api\V1\ProductMetricResource;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Services\Analysis\ProductAnalysisEngine;
use App\Services\Comparison\PriceComparisonService;
use App\Services\Product\CanonicalProductQueryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CanonicalProductQueryService $queryService,
        protected ProductAnalysisEngine $analysisEngine,
        protected PriceComparisonService $priceComparisonService
    ) {}

    /**
     * Paginated list of canonical products with eager loaded listings and metrics.
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $paginator = $this->queryService->paginate($request->validated());

        return $this->paginatedResponse(
            CanonicalProductResource::collection($paginator->items()),
            $paginator,
            'Products retrieved successfully'
        );
    }

    /**
     * Get detailed canonical product intelligence by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->resolveCanonicalProduct($id);

        $product->load([
            'category',
            'latestMetric',
            'metrics' => fn ($q) => $q->latest('calculated_at')->limit(10),
            'listings.source',
            'listings.latestMetric',
            'listings.metrics' => fn ($q) => $q->latest('calculated_at')->limit(10),
        ]);

        return $this->successResponse(
            new CanonicalProductDetailResource($product),
            'Product details retrieved successfully'
        );
    }

    /**
     * Get all marketplace listings/offers for a canonical product.
     */
    public function listings(Request $request, int $id): JsonResponse
    {
        $product = $this->resolveCanonicalProduct($id);

        $listings = $product->listings()
            ->with(['source', 'category', 'latestMetric'])
            ->get();

        return $this->successResponse(
            ProductListingResource::collection($listings),
            'Product listings retrieved successfully'
        );
    }

    /**
     * Compare multi-market prices and calculate market spread & potential margin.
     */
    public function priceComparison(Request $request, int $id): JsonResponse
    {
        $product = $this->resolveCanonicalProduct($id);
        $targetCurrency = $request->query('currency');

        $result = $this->priceComparisonService->compare($product, $targetCurrency);

        return $this->successResponse(
            new PriceComparisonResource($result),
            'Price comparison completed successfully'
        );
    }

    /**
     * Calculate and persist financial analysis metrics for a canonical product.
     */
    public function analyze(AnalyzeProductRequest $request, int $id): JsonResponse
    {
        $product = $this->resolveCanonicalProduct($id);

        $metric = $this->analysisEngine->calculateAndPersist($product, $request->validated());

        return $this->successResponse(
            new ProductMetricResource($metric),
            'Product analysis completed successfully',
            201
        );
    }

    /**
     * Resolve CanonicalProduct by ID, with graceful fallback to Listing's Canonical parent.
     */
    protected function resolveCanonicalProduct(int $id): CanonicalProduct
    {
        $product = CanonicalProduct::find($id);

        if (! $product) {
            $listing = ProductListing::find($id);
            if ($listing && $listing->canonical_product_id) {
                $product = CanonicalProduct::find($listing->canonical_product_id);
            }
        }

        if (! $product) {
            abort(404, "Product [{$id}] not found.");
        }

        return $product;
    }
}
