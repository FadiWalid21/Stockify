<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId),
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'type' => [
                'nullable',
                Rule::in(['in', 'out']),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sort_by' => [
                'nullable',
                Rule::in(['created_at', 'quantity']),
            ],

            'sort_direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}