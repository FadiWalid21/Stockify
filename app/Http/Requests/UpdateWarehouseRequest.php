<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        $warehouse = $this->route('warehouse');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($warehouse->id),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($warehouse->id),
            ],

            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}