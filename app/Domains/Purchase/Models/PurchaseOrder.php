<?php

namespace App\Domains\Purchase\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use App\Domains\Supplier\Models\Supplier;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends TenantModel
{
    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'user_id',
        'approved_by',
        'cancelled_by',
        'order_number',
        'status',
        'order_date',
        'expected_at',
        'approved_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_at' => 'date',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class);
    }
}