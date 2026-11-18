<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class CustomerReportRequest extends FormRequest
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

            'customer_id' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'search' => ['sometimes', 'nullable', 'string', 'max:255'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'sort_by' => [
                'sometimes',
                'in:customer_name,invoice_count,total_invoiced,amount_collected,outstanding_amount',
            ],

            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ];
    }
}