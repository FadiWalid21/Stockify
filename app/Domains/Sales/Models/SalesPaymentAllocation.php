<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPaymentAllocation extends TenantModel
{
    protected $fillable = [
        'sales_payment_id',
        'sales_invoice_id',
        'customer_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}