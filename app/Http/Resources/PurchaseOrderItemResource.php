<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receivedQuantity = (float) $this->receiptItems->sum('quantity_received');

        return [
            'id' => $this->id,

            'product' => new ProductResource(
                $this->whenLoaded('product')
            ),

            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,

            'received_quantity' => number_format($receivedQuantity, 4, '.', ''),

            'remaining_quantity' => number_format(
                max(0, (float) $this->quantity - $receivedQuantity),
                4,
                '.',
                ''
            ),
        ];
    }
}