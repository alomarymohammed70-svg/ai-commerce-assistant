<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'selling_price' => $this->selling_price,
            'purchase_price' => $this->purchase_price,
            'shipping_cost' => $this->shipping_cost,
            'estimated_fees' => $this->estimated_fees,
            'other_costs' => $this->other_costs,
            'estimated_profit' => $this->estimated_profit,
            'profit_margin' => $this->profit_margin,
            'return_on_investment' => $this->return_on_investment,
            'opportunity_score' => $this->opportunity_score,
            'risk_score' => $this->risk_score,
            'competition_level' => $this->competition_level,
            'calculation_notes' => $this->calculation_notes,
            'calculated_at' => $this->calculated_at?->toISOString(),
        ];
    }
}

