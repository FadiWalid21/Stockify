<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_number' => $this->return_number,

            'status' => $this->status->value,
            'return_date' => $this->return_date?->toDateString(),

            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ],

            'sales_order' => [
                'id' => $this->salesOrder?->id,
                'order_number' => $this->salesOrder?->order_number,
            ],

            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'approved_by' => [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->name,
            ],

            'received_by' => [
                'id' => $this->receivedBy?->id,
                'name' => $this->receivedBy?->name,
            ],

            'approved_at' => $this->approved_at,
            'received_at' => $this->received_at,
            'completed_at' => $this->completed_at,

            'subtotal' => $this->subtotal,
            'reason' => $this->reason,
            'notes' => $this->notes,

            'items' => SalesReturnItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
