<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'source' => new ProductSourceResource($this->whenLoaded('source')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'brand' => $this->brand,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'currency' => $this->currency,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'is_available' => $this->is_available,
            'image_url' => $this->image_url,
            'latest_metric' => new ProductMetricResource($this->whenLoaded('latestMetric')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

