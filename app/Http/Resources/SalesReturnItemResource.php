<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'sales_order_id' => $this->sales_order_id,
            'sales_order_item_id' => $this->sales_order_item_id,

            'sales_order_fulfillment_id' => $this->sales_order_fulfillment_id,
            'sales_order_fulfillment_item_id' => $this->sales_order_fulfillment_item_id,

            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku,
            ],

            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
        ];
    }
}
