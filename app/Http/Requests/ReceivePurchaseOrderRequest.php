<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'reference' => ['nullable', 'string', 'max:100'],

            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],

            'items.*.purchase_order_item_id' => [
                'required',
                'integer',
                Rule::exists('purchase_order_items', 'id')
                    ->where('company_id', $companyId),
                'distinct',
            ],

            'items.*.quantity_received' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,4',
            ],
        ];
    }
}