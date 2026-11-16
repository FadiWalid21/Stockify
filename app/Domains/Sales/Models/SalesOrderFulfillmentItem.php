<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderFulfillmentItem extends TenantModel
{
    protected $fillable = [
        'sales_order_id',
        'sales_order_fulfillment_id',
        'sales_order_item_id',
        'quantity_dispatched',
    ];

    protected function casts(): array
    {
        return [
            'quantity_dispatched' => 'decimal:4',
        ];
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(
            SalesOrderFulfillment::class,
            'sales_order_fulfillment_id'
        );
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}