<?php

namespace App\Domains\Supplier\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends TenantModel
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'tax_number',
        'contact_person',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
