<?php

namespace App\Domains\Returns\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Customer\Models\Customer;
use App\Domains\Returns\Enums\SalesReturnStatus;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends TenantModel
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'sales_order_id',
        'warehouse_id',
        'user_id',
        'approved_by',
        'received_by',
        'return_number',
        'status',
        'return_date',
        'approved_at',
        'received_at',
        'completed_at',
        'subtotal',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesReturnStatus::class,
            'return_date' => 'date',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'subtotal' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}