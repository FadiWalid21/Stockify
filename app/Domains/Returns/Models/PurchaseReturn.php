<?php

namespace App\Domains\Returns\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\Returns\Enums\PurchaseReturnStatus;
use App\Domains\Supplier\Models\Supplier;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends TenantModel
{
    protected $fillable = [
        'company_id',
        'supplier_id',
        'purchase_order_id',
        'warehouse_id',
        'user_id',
        'approved_by',
        'dispatched_by',
        'return_number',
        'status',
        'return_date',
        'approved_at',
        'dispatched_at',
        'completed_at',
        'subtotal',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseReturnStatus::class,
            'return_date' => 'date',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'subtotal' => 'decimal:4',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
