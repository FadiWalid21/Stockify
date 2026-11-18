<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class InventoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'warehouse_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'search' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'stock_status' => [
                'sometimes',
                'required',
                'in:in_stock,out_of_stock',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'sort_by' => [
                'sometimes',
                'in:quantity,created_at',
            ],
            'sort_direction' => [
                'sometimes',
                'in:asc,desc',
            ],
        ];
    }
}