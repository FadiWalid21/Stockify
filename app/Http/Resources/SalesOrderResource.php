<?php

namespace App\Http\Resources;

use App\Domains\Sales\Models\SalesOrderFulfillment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,

            'order_date' => $this->order_date?->toDateString(),
            'expected_at' => $this->expected_at?->toDateString(),

            'placed_at' => $this->placed_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'subtotal' => $this->subtotal,
            'notes' => $this->notes,

            'customer' => CustomerResource::make(
                $this->whenLoaded('customer')
            ),

            'warehouse' => WarehouseResource::make(
                $this->whenLoaded('warehouse')
            ),

            'items' => SalesOrderResource::collection(
                $this->whenLoaded('items')
            ),

            'fulfillments' => SalesOrderFulfillmentResource::collection(
                $this->whenLoaded('fulfillments')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}