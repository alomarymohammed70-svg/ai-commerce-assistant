<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Analysis\StandaloneAnalysisRequest;
use App\Services\Analysis\ProductAnalysisEngine;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductAnalysisEngine $analysisEngine
    ) {}

    /**
     * Perform deterministic standalone profitability calculation.
     */
    public function calculate(StandaloneAnalysisRequest $request): JsonResponse
    {
        $results = $this->analysisEngine->calculate($request->validated());

        return $this->successResponse(
            $results,
            'Standalone calculation completed successfully'
        );
    }
}

