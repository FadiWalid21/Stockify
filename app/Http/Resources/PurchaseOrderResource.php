<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,

            'supplier' => new SupplierResource(
                $this->whenLoaded('supplier')
            ),

            'warehouse' => new WarehouseResource(
                $this->whenLoaded('warehouse')
            ),

            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),

            'approved_by' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                ];
            }),

            'cancelled_by' => $this->whenLoaded('canceller', function () {
                return [
                    'id' => $this->canceller->id,
                    'name' => $this->canceller->name,
                ];
            }),

            'order_date' => $this->order_date,
            'expected_at' => $this->expected_at,
            'approved_at' => $this->approved_at,
            'cancelled_at' => $this->cancelled_at,
            'notes' => $this->notes,

            'items' => PurchaseOrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'receipts' => PurchaseOrderReceiptResource::collection(
                $this->whenLoaded('receipts')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}