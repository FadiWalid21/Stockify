<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'to_warehouse_id' => [
                'required',
                'integer',
                'different:from_warehouse_id',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId),
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,4',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
