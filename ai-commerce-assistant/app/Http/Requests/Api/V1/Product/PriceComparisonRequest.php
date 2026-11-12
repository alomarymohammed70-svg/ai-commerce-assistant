<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class PriceComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('target_currency') && is_string($this->input('target_currency'))) {
            $normalized = strtoupper(trim($this->input('target_currency')));
            $this->merge([
                'target_currency' => $normalized === '' ? null : $normalized,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'target_currency' => ['nullable', 'string', 'size:3', 'in:USD,EUR,GBP,CNY,SAR,AED,YER'],
        ];
    }
}

