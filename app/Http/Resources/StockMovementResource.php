<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => [
                'id' => $this->product_id,
                'name' => $this->whenLoaded('product', fn () => $this->product->name),
                'sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            ],

            'warehouse' => [
                'id' => $this->warehouse_id,
                'name' => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
                'code' => $this->whenLoaded('warehouse', fn () => $this->warehouse->code),
            ],

            'inventory_operation' => [
                'id' => $this->inventory_operation_id,
                'type' => $this->whenLoaded(
                    'inventoryOperation',
                    fn () => $this->inventoryOperation->type->value
                ),
                'reference' => $this->whenLoaded(
                    'inventoryOperation',
                    fn () => $this->inventoryOperation->reference
                ),
            ],

            'quantity' => $this->quantity,
            'type' => $this->type->value,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
