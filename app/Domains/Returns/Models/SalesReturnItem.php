<?php

namespace App\Domains\Returns\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Product\Models\Product;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesOrderFulfillment;
use App\Domains\Sales\Models\SalesOrderFulfillmentItem;
use App\Domains\Sales\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends TenantModel
{
    protected $fillable = [
        'company_id',
        'sales_return_id',
        'sales_order_id',
        'sales_order_item_id',
        'sales_order_fulfillment_id',
        'sales_order_fulfillment_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
        ];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(SalesOrderFulfillment::class, 'sales_order_fulfillment_id');
    }

    public function fulfillmentItem(): BelongsTo
    {
        return $this->belongsTo(
            SalesOrderFulfillmentItem::class,
            'sales_order_fulfillment_item_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}