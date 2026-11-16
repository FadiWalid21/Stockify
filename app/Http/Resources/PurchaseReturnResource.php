<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,
            'status' => $this->status->value,

            'supplier' => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
            ],

            'purchase_order' => $this->purchaseOrder ? [
                'id' => $this->purchaseOrder->id,
                'order_number' => $this->purchaseOrder->order_number,
            ] : null,

            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],

            'created_by' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'approved_by' => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ] : null,

            'dispatched_by' => $this->dispatchedBy ? [
                'id' => $this->dispatchedBy->id,
                'name' => $this->dispatchedBy->name,
            ] : null,

            'return_date' => $this->return_date?->toDateString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'subtotal' => $this->subtotal,
            'reason' => $this->reason,
            'notes' => $this->notes,

            'items' => PurchaseReturnItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
