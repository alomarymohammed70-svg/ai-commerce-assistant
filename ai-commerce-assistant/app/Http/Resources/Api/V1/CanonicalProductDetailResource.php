<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanonicalProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeListings = $this->relationLoaded('listings') ? $this->listings->where('is_available', true) : collect();
        $minPrice = $activeListings->isNotEmpty() ? $activeListings->min('price') : null;
        $maxPrice = $activeListings->isNotEmpty() ? $activeListings->max('price') : null;
        $firstListing = $this->relationLoaded('listings') ? $this->listings->first() : null;

        $latestMetric = $this->latestMetric;
        if (! $latestMetric && $this->relationLoaded('listings')) {
            $latestMetric = $this->listings->map->latestMetric->filter()->sortByDesc('calculated_at')->first();
        }

        $metricsHistory = $this->relationLoaded('metrics') && $this->metrics->isNotEmpty()
            ? $this->metrics
            : ($this->relationLoaded('listings') ? $this->listings->flatMap->metrics->sortByDesc('calculated_at')->values() : collect());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->name, // Compatibility alias
            'slug' => $this->slug,
            'brand' => $this->brand,
            'model_number' => $this->model_number,
            'description' => $this->description,
            'base_currency' => $this->base_currency,
            'currency' => $this->base_currency,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'listings_summary' => [
                'total_listings' => $this->relationLoaded('listings') ? $this->listings->count() : 0,
                'active_listings' => $activeListings->count(),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
            'price' => $minPrice !== null ? (string) $minPrice : '0.0000',
            'external_id' => $firstListing?->external_id,
            'listings' => ProductListingResource::collection($this->whenLoaded('listings')),
            'latest_metric' => $latestMetric ? new ProductMetricResource($latestMetric) : null,
            'metrics_history' => ProductMetricResource::collection($metricsHistory),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
