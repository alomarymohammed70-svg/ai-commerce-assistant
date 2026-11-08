<?php

namespace App\Http\Controllers\Api\V1\MarketResearch;

use App\Exceptions\Ebay\EbayIntegrationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MarketResearch\EbayItemDetailsRequest;
use App\Http\Requests\Api\V1\MarketResearch\EbaySearchRequest;
use App\Http\Resources\Api\V1\MarketResearch\MarketSearchResultResource;
use App\Http\Resources\Api\V1\ProductListingResource;
use App\Services\MarketResearch\MarketResearchService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class EbayMarketResearchController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected MarketResearchService $marketResearchService
    ) {}

    /**
     * Search live or mock eBay marketplace items.
     */
    public function search(EbaySearchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = (string) $validated['q'];
        $fresh = $request->boolean('fresh', false);
        $store = $request->boolean('store', true);

        try {
            $result = $this->marketResearchService->searchEbay($query, $validated, $fresh, $store);

            return $this->successResponse(
                new MarketSearchResultResource($result),
                'eBay market search completed successfully'
            );
        } catch (EbayIntegrationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrorResponse()
            );
        }
    }

    /**
     * Fetch single eBay item details by external item ID.
     */
    public function show(EbayItemDetailsRequest $request, string $externalId): JsonResponse
    {
        $validated = $request->validated();
        $fresh = $request->boolean('fresh', false);
        $store = $request->boolean('store', true);

        try {
            $result = $this->marketResearchService->getEbayItem($externalId, $validated, $fresh, $store);

            if ($result === null) {
                return $this->errorResponse("eBay item [{$externalId}] was not found.", 404);
            }

            $includeRaw = $request->boolean('include_raw', false);

            $payload = [
                'external_id' => $result['external_id'],
                'source' => $result['source'],
                'is_mock' => $result['is_mock'],
                'cached' => $result['cached'],
                'duration_ms' => $result['duration_ms'],
                'listing' => $result['listing'] ? new ProductListingResource($result['listing']) : null,
                'raw_payload' => $includeRaw ? $result['raw_payload'] : null,
            ];

            return $this->successResponse(
                $payload,
                'eBay item details retrieved successfully'
            );
        } catch (EbayIntegrationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrorResponse()
            );
        }
    }
}

