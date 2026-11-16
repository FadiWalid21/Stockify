<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use \App\Domains\Company\Contracts\TenantContextInterface;

class DispatchSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.sales_order_item_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('sales_order_items', 'id')
                    ->where('company_id', app(TenantContextInterface::class)->getCompanyId()),
            ],

            'items.*.quantity_dispatched' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }
}