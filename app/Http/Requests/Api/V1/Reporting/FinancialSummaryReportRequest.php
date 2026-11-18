<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class FinancialSummaryReportRequest extends FormRequest
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
                'date',
            ],

            'to' => [
                'sometimes',
                'date',
                'after_or_equal:from',
            ],

            'customer_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'supplier_id' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }
}