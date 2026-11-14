<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'all']),
            ],

            'category_id' => [
                'sometimes',
                'integer',
            ],

            'unit_id' => [
                'sometimes',
                'integer',
            ],

            'sort_by' => [
                'sometimes',
                Rule::in(['name', 'sku', 'created_at']),
            ],

            'sort_direction' => [
                'sometimes',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}