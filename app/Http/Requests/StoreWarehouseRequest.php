<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')
                    ->where('company_id', $companyId),
            ],

            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')
                    ->where('company_id', $companyId),
            ],

            'address' => ['nullable', 'string', 'max:1000'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}