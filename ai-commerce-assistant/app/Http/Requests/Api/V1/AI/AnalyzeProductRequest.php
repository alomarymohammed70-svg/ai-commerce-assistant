<?php

namespace App\Http\Requests\Api\V1\AI;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnalyzeProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_currency' => ['nullable', 'string', 'size:3'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'selling_currency' => ['nullable', 'string', 'size:3'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'shipping_currency' => ['nullable', 'string', 'size:3'],
            'estimated_fees' => ['nullable', 'numeric', 'min:0'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'save' => ['nullable', 'boolean'],
            'driver' => ['nullable', 'string', 'in:mock,openai'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('save')) {
            $this->merge([
                'save' => filter_var($this->input('save'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}

