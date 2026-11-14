<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)
            ->getCompanyId();

        $product = $this->route('product');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('company_id', $companyId)
                    ->ignore($product->id),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('company_id', $companyId),
            ],

            'unit_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('units', 'id'),
            ],
        ];
    }
}
