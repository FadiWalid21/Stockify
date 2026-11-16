<?php

namespace App\Domains\Purchase\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderReceipt extends TenantModel
{
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'received_at',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceiptItem::class);
    }
}