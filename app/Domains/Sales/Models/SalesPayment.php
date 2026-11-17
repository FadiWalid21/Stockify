<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Customer\Models\Customer;
use App\Domains\User\Models\User;

class SalesPayment extends TenantModel
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_id',
        'user_id',
        'payment_number',
        'payment_method',
        'status',
        'payment_date',
        'amount',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            SalesPaymentAllocation::class,
            'sales_payment_id'
        );
    }
}