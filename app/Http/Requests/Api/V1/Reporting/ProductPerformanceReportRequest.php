<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class ProductPerformanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'required', 'date'],
            'to' => ['sometimes', 'required', 'date', 'after_or_equal:from'],

            'product_id' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'search' => ['sometimes', 'nullable', 'string', 'max:255'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'sort_by' => [
                'sometimes',
                'in:quantity_sold,sales_value,total_value,product_name',
            ],

            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ];
    }
}