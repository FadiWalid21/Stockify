<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;

class SalesInvoiceSequence extends TenantModel
{
    protected $fillable = [
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}