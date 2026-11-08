<?php

namespace App\Http\Resources\Api\V1\MarketResearch;

use App\Http\Resources\Api\V1\ProductListingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketSearchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeRaw = $request->boolean('include_raw', false);

        $items = ! empty($this['listings'])
            ? ProductListingResource::collection($this['listings'])
            : array_map(function ($dto) use ($includeRaw) {
                return [
                    'external_id' => $dto->externalId,
                    'title' => $dto->title,
                    'brand' => $dto->brand,
                    'price' => number_format($dto->price, 4, '.', ''),
                    'currency' => $dto->currency,
                    'rating' => $dto->rating,
                    'reviews_count' => $dto->reviewsCount,
                    'url' => $dto->url,
                    'image_url' => $dto->imageUrl,
                    'is_available' => $dto->isAvailable,
                    'raw_payload' => $includeRaw ? $dto->rawPayload : null,
                ];
            }, $this['dtos'] ?? []);

        return [
            'query' => $this['query'],
            'source' => $this['source'],
            'is_mock' => $this['is_mock'],
            'cached' => $this['cached'],
            'duration_ms' => $this['duration_ms'],
            'pagination' => [
                'total' => $this['total'],
                'limit' => $this['limit'],
                'offset' => $this['offset'],
                'count' => $this['count'],
            ],
            'items' => $items,
            'raw_payload' => $this->when($includeRaw, $this['raw_payload']),
        ];
    }
}

