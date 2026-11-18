<?php

namespace App\Http\Requests\Api\V1\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class SalesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'required', 'date'],
            'to' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:from',
            ],
            'customer_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'group_by' => [
                'sometimes',
                'required',
                'in:day,month',
            ],
        ];
    }
}