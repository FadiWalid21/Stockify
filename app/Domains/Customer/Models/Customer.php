<?php

namespace App\Domains\Customer\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesPayment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends TenantModel
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

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesPayments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }
}