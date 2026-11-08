<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductSourceResource;
use App\Models\ProductSource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class ProductSourceController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all supported marketplace sources.
     */
    public function index(): JsonResponse
    {
        $sources = ProductSource::query()
            ->active()
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            ProductSourceResource::collection($sources),
            'Product sources retrieved successfully'
        );
    }
}

