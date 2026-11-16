<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();
        $customerId = $this->route('customer');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($customerId),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($customerId),
            ],

            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],

            'tax_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers', 'tax_number')
                    ->where('company_id', $companyId)
                    ->ignore($customerId),
            ],

            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}