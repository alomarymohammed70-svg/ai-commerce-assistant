<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Http\Resources\Api\V1\ProductListingResource;
use App\Models\ProductListing;
use App\Services\Product\ProductQueryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductListingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductQueryService $queryService
    ) {}

    /**
     * Paginated list of marketplace listings across all sources.
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $paginator = $this->queryService->paginate($request->validated());

        return $this->paginatedResponse(
            ProductListingResource::collection($paginator->items()),
            $paginator,
            'Marketplace listings retrieved successfully'
        );
    }

    /**
     * Single marketplace listing details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $listing = ProductListing::with([
            'source',
            'category',
            'canonicalProduct',
            'latestMetric',
        ])->findOrFail($id);

        return $this->successResponse(
            new ProductListingResource($listing),
            'Marketplace listing details retrieved successfully'
        );
    }
}

