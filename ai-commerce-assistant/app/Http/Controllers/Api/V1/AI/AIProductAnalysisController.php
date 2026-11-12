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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
        $driver = $this->resolveAuthorizedDriverOverride($request, $validated['driver'] ?? null);
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
        $driver = $this->resolveAuthorizedDriverOverride($request, $validated['driver'] ?? null);
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
    public function show(Request $request, int|string $id): JsonResponse
    {
        if (! ctype_digit((string) $id) || (int) $id <= 0) {
            return $this->errorResponse('AI Analysis not found.', null, 404);
        }

        $analysis = AIAnalysis::find((int) $id);

        if (! $analysis) {
            return $this->errorResponse('AI Analysis not found.', null, 404);
        }

        Gate::forUser($request->user())->authorize('view', $analysis);

        $analysis->load(['canonicalProduct', 'productListing']);

        return $this->successResponse(
            new AIAnalysisResource($analysis),
            'Historical AI analysis retrieved successfully.'
        );
    }

    /**
     * Only permit AI driver overrides for authenticated admins in non-production environments.
     * In production and for all regular users, configured server AI_PROVIDER remains authoritative.
     */
    protected function resolveAuthorizedDriverOverride(Request $request, ?string $requestedDriver): ?string
    {
        if ($requestedDriver === null || trim($requestedDriver) === '') {
            return null;
        }

        $user = $request->user();
        if ($user !== null && $user->isAdmin() && app()->environment(['local', 'testing'])) {
            return strtolower(trim($requestedDriver));
        }

        Log::warning('ai.driver_override_ignored', [
            'user_id' => $user?->id,
            'requested_driver' => $requestedDriver,
            'configured_default' => config('ai.default', 'mock'),
            'environment' => app()->environment(),
        ]);

        return null;
    }
}
