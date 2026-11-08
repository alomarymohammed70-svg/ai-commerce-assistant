<?php

namespace App\Http\Requests\Api\V1\MarketResearch;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EbaySearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:500'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'condition' => ['nullable', 'string', 'in:NEW,USED,REFURBISHED,FOR_PARTS'],
            'currency' => ['nullable', 'string', 'size:3'],
            'include_raw' => ['nullable', 'boolean'],
            'fresh' => ['nullable', 'boolean'],
            'store' => ['nullable', 'boolean'],
            'driver' => ['nullable', 'string', 'in:mock,sandbox,production'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search query string [q] is required.',
            'q.min' => 'Search query must contain at least 2 characters.',
            'limit.max' => 'Maximum allowed limit is 50 items per search page.',
            'min_price.min' => 'Minimum price must be a non-negative number.',
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
