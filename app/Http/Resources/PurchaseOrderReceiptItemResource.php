<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'purchase_order_item_id' => $this->purchase_order_item_id,

            'product' => new ProductResource(
                $this->whenLoaded('purchaseOrderItem')->product
            ),

            'quantity_received' => $this->quantity_received,
        ];
    }
}