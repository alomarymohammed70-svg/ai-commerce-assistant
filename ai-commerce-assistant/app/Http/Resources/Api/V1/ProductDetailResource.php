<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProductMetric;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = ProductMetric::resolveApiViewer($request);

        return [
            'id' => $this->id,
            'canonical_product_id' => $this->canonical_product_id,
            'external_id' => $this->external_id,
            'source' => new ProductSourceResource($this->whenLoaded('source')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'description' => $this->description,
            'brand' => $this->brand,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'currency' => $this->currency,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'is_available' => $this->is_available,
            'observed_at' => $this->observed_at?->toISOString(),
            'latest_metric' => $this->whenLoaded('latestMetric', function () use ($viewer) {
                return $this->latestMetric instanceof ProductMetric && $this->latestMetric->isVisibleTo($viewer)
                    ? new ProductMetricResource($this->latestMetric)
                    : null;
            }),
            'metrics_history' => $this->whenLoaded('metrics', function () use ($viewer) {
                return ProductMetricResource::collection(
                    $this->metrics->filter(fn ($m) => $m instanceof ProductMetric && $m->isVisibleTo($viewer))->values()
                );
            }),
            'raw_payload' => $this->when($request->boolean('include_raw', false), $this->raw_payload),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
