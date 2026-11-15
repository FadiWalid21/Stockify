<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'reference' => $this->reference,
            'notes' => $this->notes,

            'stock_movements' => StockMovementResource::collection(
                $this->whenLoaded('stockMovements')
            ),

            'created_at' => $this->created_at,
        ];
    }
}
