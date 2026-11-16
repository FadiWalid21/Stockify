<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
                Rule::unique('suppliers', 'name')
                    ->where('company_id', $companyId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')
                    ->where('company_id', $companyId),
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'tax_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('suppliers', 'tax_number')
                    ->where('company_id', $companyId),
            ],

            'contact_person' => ['nullable', 'string', 'max:255'],

            'address' => ['nullable', 'string'],

            'notes' => ['nullable', 'string'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
