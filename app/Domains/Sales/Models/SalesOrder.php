<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Customer\Models\Customer;
use App\Domains\Sales\Enums\SalesOrderStatus;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends TenantModel
{
    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'user_id',
        'confirmed_by',
        'cancelled_by',
        'order_number',
        'status',
        'order_date',
        'expected_at',
        'placed_at',
        'confirmed_at',
        'cancelled_at',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'expected_at' => 'date',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(SalesOrderFulfillment::class);
    }
}