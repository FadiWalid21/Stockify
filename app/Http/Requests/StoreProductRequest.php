<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)
            ->getCompanyId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('company_id', $companyId),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('company_id', $companyId),
            ],

            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id'),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
