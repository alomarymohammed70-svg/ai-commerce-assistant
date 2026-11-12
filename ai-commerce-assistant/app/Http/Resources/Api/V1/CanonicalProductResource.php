<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\BuildsCanonicalProductPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanonicalProductResource extends JsonResource
{
    use BuildsCanonicalProductPayload;

    public function toArray(Request $request): array
    {
        return $this->buildCanonicalProductPayload($request, includeMetricsHistory: false);
    }
}
