<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku,
            ],

            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'line_total' => $this->line_total,

            'purchase_history' => [
                'purchase_order_id' => $this->purchase_order_id,
                'purchase_order_item_id' => $this->purchase_order_item_id,
                'purchase_order_receipt_id' => $this->purchase_order_receipt_id,
                'purchase_order_receipt_item_id' => $this->purchase_order_receipt_item_id,
                'purchase_order_number' => $this->purchaseOrder?->order_number,
            ],
        ];
    }
}
