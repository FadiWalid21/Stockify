<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId),
            ],

            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'counted_quantity' => [
                'required',
                'numeric',
                'gte:0',
                'decimal:0,4',
            ],

            'reason' => [
                'required',
                'string',
                'max:1000',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}