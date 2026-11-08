<?php

namespace App\Http\Resources\Api\V1\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? $this->resource->id ?? null,
            'canonical_product_id' => $this['canonical_product_id'] ?? $this->resource->canonical_product_id ?? null,
            'product_listing_id' => $this['product_listing_id'] ?? $this->resource->product_listing_id ?? null,
            'provider' => $this['provider'] ?? $this->resource->provider ?? null,
            'model' => $this['model'] ?? $this->resource->model ?? null,
            'prompt_version' => $this['prompt_version'] ?? $this->resource->prompt_version ?? 'v1',
            'decision' => $this['decision'] ?? $this->resource->decision ?? null,
            'confidence' => $this['confidence'] ?? $this->resource->confidence ?? null,
            'opportunity_score' => $this['opportunity_score'] ?? $this->resource->opportunity_score ?? null,
            'risk_score' => $this['risk_score'] ?? $this->resource->risk_score ?? null,
            'analysis' => $this['analysis'] ?? $this->resource->ai_response ?? [],
            'deterministic_metrics' => $this['deterministic_metrics'] ?? $this->resource->deterministic_metrics ?? [],
            'duration_ms' => $this['duration_ms'] ?? $this->resource->duration_ms ?? null,
            'tokens_used' => $this['tokens_used'] ?? $this->resource->tokens_used ?? null,
            'created_at' => $this['created_at'] ?? $this->resource->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}

