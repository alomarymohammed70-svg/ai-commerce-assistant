<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Exceptions\AI\AIException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AI\AnalyzeProductRequest;
use App\Http\Resources\Api\V1\AI\AIAnalysisResource;
use App\Models\AIAnalysis;
use App\Models\CanonicalProduct;
use App\Models\ProductListing;
use App\Services\AI\Recommendation\AIRecommendationEngine;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AIProductAnalysisController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AIRecommendationEngine $engine
    ) {}

    /**
     * Run AI commerce intelligence analysis on a Canonical Product.
     */
    public function analyzeProduct(AnalyzeProductRequest $request, int|string $id): JsonResponse
    {
        $canonical = CanonicalProduct::with(['category', 'activeListings.source'])->find($id);

        if (! $canonical) {
            return $this->errorResponse("Canonical Product [{$id}] not found.", null, 404);
        }

        $validated = $request->validated();
        $shouldSave = $request->boolean('save', true);
        $driver = $validated['driver'] ?? null;
        $userId = $request->user()?->id;

        try {
            $result = $this->engine->analyze(
                subject: $canonical,
                financialInputs: $validated,
                userId: $userId,
                driverOverride: $driver,
                shouldPersist: $shouldSave
            );

            return $this->successResponse(
                new AIAnalysisResource($result),
                'AI commerce intelligence analysis completed successfully.'
            );
        } catch (AIException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorDetails(),
                $e->getStatusCode()
            );
        }
    }

    /**
     * Run AI commerce intelligence analysis on a specific Product Listing offer.
     */
    public function analyzeListing(AnalyzeProductRequest $request, int|string $id): JsonResponse
    {
        $listing = ProductListing::with(['canonicalProduct.category', 'source', 'category'])->find($id);

        if (! $listing) {
            return $this->errorResponse("Product Listing [{$id}] not found.", null, 404);
        }

        $validated = $request->validated();
        $shouldSave = $request->boolean('save', true);
        $driver = $validated['driver'] ?? null;
        $userId = $request->user()?->id;

        try {
            $result = $this->engine->analyze(
                subject: $listing,
                financialInputs: $validated,
                userId: $userId,
                driverOverride: $driver,
                shouldPersist: $shouldSave
            );

            return $this->successResponse(
                new AIAnalysisResource($result),
                'AI listing intelligence analysis completed successfully.'
            );
        } catch (AIException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorDetails(),
                $e->getStatusCode()
            );
        }
    }

    /**
     * Retrieve a past historical AI analysis record.
     */
    public function show(int|string $id): JsonResponse
    {
        $analysis = AIAnalysis::with(['canonicalProduct', 'productListing'])->find($id);

        if (! $analysis) {
            return $this->errorResponse("AI Analysis [{$id}] not found.", null, 404);
        }

        return $this->successResponse(
            new AIAnalysisResource($analysis),
            'Historical AI analysis retrieved successfully.'
        );
    }
}
