<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],

            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'all']),
            ],

            'sort_by' => [
                'sometimes',
                'string',
                Rule::in(['name', 'email', 'created_at']),
            ],

            'sort_direction' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}