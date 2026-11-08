<?php

namespace App\Http\Requests\Api\V1\MarketResearch;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EbayItemDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('fresh')) {
            $merge['fresh'] = filter_var($this->input('fresh'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($this->has('include_raw')) {
            $merge['include_raw'] = filter_var($this->input('include_raw'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if ($this->has('store')) {
            $merge['store'] = filter_var($this->input('store'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'include_raw' => ['nullable', 'boolean'],
            'fresh' => ['nullable', 'boolean'],
            'store' => ['nullable', 'boolean'],
            'driver' => ['nullable', 'string', 'in:mock,sandbox,production'],
        ];
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
