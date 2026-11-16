<?php

namespace App\Domains\Purchase\Models;

use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReceiptItem extends TenantModel
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_order_receipt_id',
        'purchase_order_item_id',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:4',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderReceipt::class,
            'purchase_order_receipt_id'
        );
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}