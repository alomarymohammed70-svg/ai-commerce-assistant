<?php

namespace App\Http\Controllers\Api\V1\MarketResearch;

use App\Exceptions\Ebay\EbayAuthenticationException;
use App\Exceptions\Ebay\EbayIntegrationException;
use App\Exceptions\Ebay\EbayRateLimitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MarketResearch\EbayItemDetailsRequest;
use App\Http\Requests\Api\V1\MarketResearch\EbaySearchRequest;
use App\Http\Resources\Api\V1\MarketResearch\MarketSearchResultResource;
use App\Http\Resources\Api\V1\ProductListingResource;
use App\Services\MarketResearch\MarketResearchService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $validated = $request->validated();
        $validated = $this->sanitizeDriverParameter($request, $validated);

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
            return $this->handleEbayException($e);
        }
    }

    /**
     * Fetch single eBay item details by external item ID.
     */
    public function show(EbayItemDetailsRequest $request, string $externalId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $validated = $request->validated();
        $validated = $this->sanitizeDriverParameter($request, $validated);

        $fresh = $request->boolean('fresh', false);
        $store = $request->boolean('store', true);

        try {
            $result = $this->marketResearchService->getEbayItem($externalId, $validated, $fresh, $store);

            if ($result === null) {
                return $this->errorResponse("eBay item [{$externalId}] was not found.", null, 404);
            }

            $includeRaw = $request->boolean('include_raw', false);

            $payload = [
                'external_id' => $result['external_id'],
                'source' => $result['source'],
                'is_mock' => $result['is_mock'],
                'cached' => $result['cached'],
                'duration_ms' => $result['duration_ms'],
                'listing' => $result['listing'] ? new ProductListingResource($result['listing']) : null,
            ];

            if ($includeRaw) {
                $payload['raw_payload'] = $result['raw_payload'];
            }

            return $this->successResponse(
                $payload,
                'eBay item details retrieved successfully'
            );
        } catch (EbayIntegrationException $e) {
            return $this->handleEbayException($e);
        }
    }

    /**
     * Map eBay integration exceptions cleanly to API error responses without leaking internal auth status.
     */
    protected function handleEbayException(EbayIntegrationException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();

        // Upstream eBay OAuth/gateway 401/403 must never be forwarded as a client 401/403 session error.
        if ($e instanceof EbayAuthenticationException || in_array($statusCode, [401, 403], true)) {
            $statusCode = 502;
        } elseif ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 502;
        }

        $response = $this->errorResponse(
            $e->getMessage(),
            $e->getErrorResponse(),
            $statusCode
        );

        if ($e instanceof EbayRateLimitException && $e->getRetryAfterSeconds() !== null) {
            $response->header('Retry-After', (string) $e->getRetryAfterSeconds());
        }

        return $response;
    }

    /**
     * Only allow explicit driver overrides for admins in non-production environments.
     * In production and for all regular users, server EBAY_MODE remains authoritative.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function sanitizeDriverParameter(Request $request, array $validated): array
    {
        if (! isset($validated['driver']) || trim((string) $validated['driver']) === '') {
            unset($validated['driver']);
            return $validated;
        }

        $user = $request->user();
        $canOverride = $user !== null
            && $user->isAdmin()
            && app()->environment(['local', 'testing']);

        if (! $canOverride) {
            Log::warning('ebay.driver_override_ignored', [
                'user_id' => $user?->id,
                'requested_driver' => $validated['driver'],
                'configured_mode' => config('services.ebay.mode', 'mock'),
                'environment' => app()->environment(),
            ]);

            unset($validated['driver']);
        } else {
            $validated['driver'] = strtolower(trim((string) $validated['driver']));
        }

        return $validated;
    }
}

