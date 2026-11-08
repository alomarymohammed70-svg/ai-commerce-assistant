<?php

namespace App\Http\Resources\Api\V1;

use App\DTOs\Comparison\PriceComparisonResultDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceComparisonResource extends JsonResource
{
    /**
     * @param PriceComparisonResultDto $resource
     */
    public function toArray(Request $request): array
    {
        /** @var PriceComparisonResultDto $dto */
        $dto = $this->resource;

        return $dto->toArray();
    }
}

