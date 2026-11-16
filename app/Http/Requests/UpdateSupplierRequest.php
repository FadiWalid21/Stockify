<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();
        $supplierId = $this->route('supplier');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($supplierId),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($supplierId),
            ],

            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],

            'tax_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('suppliers', 'tax_number')
                    ->where('company_id', $companyId)
                    ->ignore($supplierId),
            ],

            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
