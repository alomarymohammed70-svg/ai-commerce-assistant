<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Contracts\Currency\CurrencyConverterInterface;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductListingResource;
use App\Http\Resources\Api\V1\ProductMetricResource;
use App\Http\Resources\Api\V1\ProductSourceResource;
use App\Models\ProductMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait BuildsCanonicalProductPayload
{
    /**
     * Build the single canonical product payload shared across list & details endpoints.
     *
     * @return array<string, mixed>
     */
    protected function buildCanonicalProductPayload(Request $request, bool $includeMetricsHistory = false): array
    {
        $viewer = ProductMetric::resolveApiViewer($request);
        $listings = $this->relationLoaded('listings') ? $this->listings : collect();
        $activeListings = $listings->where('is_available', true)->values();
        $firstListing = $listings->first();

        $baseCurrency = strtoupper(trim((string) ($this->base_currency ?: 'USD')));
        $priceSummary = $this->computeNormalizedPriceSummary($activeListings, $baseCurrency);

        $avgRating = $listings->isNotEmpty() ? $listings->avg('rating') : null;
        $totalReviews = (int) $listings->sum('reviews_count');

        $visibleLatestMetric = $this->resolveVisibleLatestMetric($listings, $viewer);
        $latestOpportunityScore = ($visibleLatestMetric !== null && $visibleLatestMetric->opportunity_score !== null)
            ? (float) round((float) $visibleLatestMetric->opportunity_score, 2)
            : null;

        $activeCount = $this->relationLoaded('listings')
            ? $activeListings->count()
            : (int) ($this->active_listings_count ?? 0);

        $totalCount = $this->relationLoaded('listings')
            ? $listings->count()
            : (int) ($this->total_listings_count ?? 0);

        $payload = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'model_number' => $this->model_number,
            'description' => $this->description,
            'base_currency' => $baseCurrency,
            'category' => $this->relationLoaded('category')
                ? ($this->category ? new CategoryResource($this->category) : null)
                : $this->whenLoaded('category'),
            'price_summary' => $priceSummary,
            'active_listings_count' => $activeCount,
            'total_listings_count' => $totalCount,
            'rating' => $avgRating !== null ? (float) round((float) $avgRating, 2) : null,
            'reviews_count' => $totalReviews,
            'source' => ($firstListing && $firstListing->relationLoaded('source') && $firstListing->source)
                ? new ProductSourceResource($firstListing->source)
                : null,
            'external_id' => $firstListing?->external_id,
            'latest_opportunity_score' => $latestOpportunityScore,
            'latest_metric' => $visibleLatestMetric ? new ProductMetricResource($visibleLatestMetric) : null,
            'listings' => ProductListingResource::collection($this->whenLoaded('listings')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        if ($includeMetricsHistory) {
            $metricsHistory = $this->resolveVisibleMetricsHistory($listings, $viewer);
            $payload['metrics_history'] = ProductMetricResource::collection($metricsHistory);
        }

        return $payload;
    }

    /**
     * Normalize active listing prices into canonical base_currency before aggregating.
     * Unconvertible currencies are skipped safely rather than mixing raw numbers or inventing values.
     *
     * @param Collection<int, mixed> $activeListings
     * @return array{lowest_price: ?string, highest_price: ?string, average_price: ?string, currency: string}
     */
    protected function computeNormalizedPriceSummary(Collection $activeListings, string $targetCurrency): array
    {
        if ($activeListings->isEmpty()) {
            return [
                'lowest_price' => null,
                'highest_price' => null,
                'average_price' => null,
                'currency' => $targetCurrency,
            ];
        }

        /** @var CurrencyConverterInterface $converter */
        $converter = app(CurrencyConverterInterface::class);
        $normalizedPrices = [];

        foreach ($activeListings as $listing) {
            if ($listing->price === null || ! is_numeric($listing->price)) {
                continue;
            }

            $sourceCurrency = strtoupper(trim((string) ($listing->currency ?: $targetCurrency)));

            try {
                $normalizedPrices[] = $converter->convert((string) $listing->price, $sourceCurrency, $targetCurrency);
            } catch (InvalidArgumentException) {
                // Skip unconvertible currencies instead of polluting aggregates or throwing 500 on catalog reads.
                continue;
            }
        }

        if ($normalizedPrices === []) {
            return [
                'lowest_price' => null,
                'highest_price' => null,
                'average_price' => null,
                'currency' => $targetCurrency,
            ];
        }

        $lowest = $normalizedPrices[0];
        $highest = $normalizedPrices[0];
        $sum = '0.0000';

        foreach ($normalizedPrices as $price) {
            if (bccomp($price, $lowest, 4) < 0) {
                $lowest = $price;
            }
            if (bccomp($price, $highest, 4) > 0) {
                $highest = $price;
            }
            $sum = bcadd($sum, $price, 4);
        }

        $average = bcdiv($sum, (string) count($normalizedPrices), 4);

        return [
            'lowest_price' => $lowest,
            'highest_price' => $highest,
            'average_price' => $average,
            'currency' => $targetCurrency,
        ];
    }

    /**
     * Resolve viewer-visible latest metric, preserving strict user isolation.
     */
    protected function resolveVisibleLatestMetric(Collection $listings, ?\App\Models\User $viewer): ?ProductMetric
    {
        if (
            $this->relationLoaded('latestMetric')
            && $this->latestMetric instanceof ProductMetric
            && $this->latestMetric->isVisibleTo($viewer)
        ) {
            return $this->latestMetric;
        }

        if ($listings->isEmpty()) {
            return null;
        }

        return $listings
            ->filter(fn ($listing) => $listing->relationLoaded('latestMetric'))
            ->map->latestMetric
            ->filter(fn ($metric) => $metric instanceof ProductMetric && $metric->isVisibleTo($viewer))
            ->sortByDesc(fn (ProductMetric $metric) => sprintf('%s-%010d', $metric->calculated_at?->timestamp ?? 0, $metric->id))
            ->first();
    }

    /**
     * Resolve viewer-visible metrics history for detail views.
     *
     * @return Collection<int, ProductMetric>
     */
    protected function resolveVisibleMetricsHistory(Collection $listings, ?\App\Models\User $viewer): Collection
    {
        $visibleDirectMetrics = $this->relationLoaded('metrics')
            ? $this->metrics->filter(fn ($metric) => $metric instanceof ProductMetric && $metric->isVisibleTo($viewer))->values()
            : collect();

        if ($visibleDirectMetrics->isNotEmpty()) {
            return $visibleDirectMetrics;
        }

        return $listings
            ->flatMap(fn ($listing) => $listing->relationLoaded('metrics') ? $listing->metrics : collect())
            ->filter(fn ($metric) => $metric instanceof ProductMetric && $metric->isVisibleTo($viewer))
            ->sortByDesc(fn (ProductMetric $metric) => sprintf('%s-%010d', $metric->calculated_at?->timestamp ?? 0, $metric->id))
            ->values();
    }
}
