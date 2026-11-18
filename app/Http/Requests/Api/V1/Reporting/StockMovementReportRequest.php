<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => [
                'sometimes',
                'required',
                'date',
            ],
            'to' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:from',
            ],
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
            'type' => [
                'sometimes',
                'nullable',
                'in:in,out',
            ],
            'operation_type' => [
                'sometimes',
                'nullable',
                'in:stock_in,stock_out,opening_balance,adjustment',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'sort_by' => [
                'sometimes',
                'in:created_at,quantity',
            ],
            'sort_direction' => [
                'sometimes',
                'in:asc,desc',
            ],
        ];
    }
}