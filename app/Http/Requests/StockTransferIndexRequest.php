<?php

namespace App\Http\Requests;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return [
            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'approved',
                    'in_transit',
                    'received',
                    'cancelled',
                ]),
            ],

            'from_warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'to_warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sort_by' => [
                'nullable',
                Rule::in(['created_at', 'reference', 'status']),
            ],

            'sort_direction' => [
                'nullable',
                Rule::in(['asc', 'desc']),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
