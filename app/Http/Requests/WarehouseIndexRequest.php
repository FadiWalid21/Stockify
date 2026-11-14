<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],

            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'all']),
            ],

            'sort_by' => [
                'sometimes',
                Rule::in(['name', 'code', 'created_at']),
            ],

            'sort_direction' => [
                'sometimes',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}