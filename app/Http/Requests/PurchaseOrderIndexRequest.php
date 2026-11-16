<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'approved',
                    'partially_received',
                    'received',
                    'cancelled',
                ]),
            ],

            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],

            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],

            'sort_by' => [
                'nullable',
                Rule::in([
                    'order_number',
                    'order_date',
                    'expected_at',
                    'created_at',
                ]),
            ],

            'sort_direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],

            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}