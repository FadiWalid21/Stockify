<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryBalanceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

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

            'sort_by' => [
                'nullable',
                Rule::in([
                    'quantity',
                    'created_at',
                ]),
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