<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'received_at' => $this->received_at,
            'reference' => $this->reference,
            'notes' => $this->notes,

            'received_by' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'items' => PurchaseOrderReceiptItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}