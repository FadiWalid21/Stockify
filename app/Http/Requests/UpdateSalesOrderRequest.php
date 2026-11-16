<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],

            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],

            'order_date' => ['sometimes', 'required', 'date'],
            'expected_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'gte:0'],
        ];
    }
}