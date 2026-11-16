<?php

namespace App\Domains\Returns\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Product\Models\Product;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\Purchase\Models\PurchaseOrderItem;
use App\Domains\Purchase\Models\PurchaseOrderReceipt;
use App\Domains\Purchase\Models\PurchaseOrderReceiptItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends TenantModel
{
    protected $fillable = [
        'company_id',
        'purchase_return_id',
        'purchase_order_id',
        'purchase_order_item_id',
        'purchase_order_receipt_id',
        'purchase_order_receipt_item_id',
        'product_id',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function purchaseOrderReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceipt::class);
    }

    public function purchaseOrderReceiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceiptItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
