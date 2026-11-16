<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderFulfillmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sales_order_id' => $this->sales_order_id,
            'sales_order_fulfillment_id' => $this->sales_order_fulfillment_id,
            'sales_order_item_id' => $this->sales_order_item_id,
            'quantity_dispatched' => $this->quantity_dispatched,

            'sales_order_item' => SalesOrderItemResource::make(
                $this->whenLoaded('salesOrderItem')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}