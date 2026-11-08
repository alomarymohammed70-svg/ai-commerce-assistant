<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanonicalProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstListing = $this->relationLoaded('listings') ? $this->listings->first() : null;
        $activeListings = $this->relationLoaded('listings') ? $this->listings->where('is_available', true) : collect();

        $minPrice = $activeListings->isNotEmpty() ? $activeListings->min('price') : null;
        $avgRating = $this->relationLoaded('listings') && $this->listings->isNotEmpty() ? $this->listings->avg('rating') : null;
        $totalReviews = $this->relationLoaded('listings') ? (int) $this->listings->sum('reviews_count') : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->name, // Backward compatibility alias
            'slug' => $this->slug,
            'brand' => $this->brand,
            'model_number' => $this->model_number,
            'description' => $this->description,
            'base_currency' => $this->base_currency,
            'currency' => $this->base_currency, // Backward compatibility alias
            'category' => new CategoryResource($this->whenLoaded('category')),
            'listings_count' => $this->relationLoaded('listings') ? $this->listings->count() : ($this->listings_count ?? 0),
            'price' => $minPrice !== null ? (string) $minPrice : '0.0000',
            'rating' => $avgRating !== null ? (float) round($avgRating, 2) : null,
            'reviews_count' => $totalReviews,
            'source' => $firstListing && $firstListing->relationLoaded('source') ? new ProductSourceResource($firstListing->source) : null,
            'external_id' => $firstListing?->external_id,
            'listings' => ProductListingResource::collection($this->whenLoaded('listings')),
            'latest_metric' => new ProductMetricResource($this->whenLoaded('latestMetric')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

