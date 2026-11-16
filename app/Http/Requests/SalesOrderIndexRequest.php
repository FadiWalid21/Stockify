<?php

namespace App\Http\Requests;

use App\Domains\Sales\Enums\SalesOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesOrderIndexRequest extends FormRequest
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
                'nullable',
                Rule::enum(SalesOrderStatus::class),
            ],

            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer'],

            'sort_by' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'order_number',
                    'order_date',
                    'subtotal',
                    'created_at',
                ]),
            ],

            'sort_direction' => [
                'sometimes',
                'nullable',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}