<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProductMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = ProductMetric::resolveApiViewer($request);

        return [
            'id' => $this->id,
            'canonical_product_id' => $this->canonical_product_id,
            'external_id' => $this->external_id,
            'source' => $this->relationLoaded('source')
                ? ($this->source ? new ProductSourceResource($this->source) : null)
                : $this->whenLoaded('source'),
            'category' => $this->relationLoaded('category')
                ? ($this->category ? new CategoryResource($this->category) : null)
                : $this->whenLoaded('category'),
            'title' => $this->title,
            'brand' => $this->brand,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'currency' => $this->currency,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'is_available' => $this->is_available,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'observed_at' => $this->observed_at?->toISOString(),
            'seller' => $this->extractSellerSummary(),
            'latest_metric' => $this->whenLoaded('latestMetric', function () use ($viewer) {
                return $this->latestMetric instanceof ProductMetric && $this->latestMetric->isVisibleTo($viewer)
                    ? new ProductMetricResource($this->latestMetric)
                    : null;
            }),
            'raw_payload' => $this->when($request->boolean('include_raw', false), $this->raw_payload),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

