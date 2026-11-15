<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'notes' => $this->notes,

            'from_warehouse' => [
                'id' => $this->from_warehouse_id,
                'name' => $this->whenLoaded(
                    'fromWarehouse',
                    fn () => $this->fromWarehouse->name
                ),
                'code' => $this->whenLoaded(
                    'fromWarehouse',
                    fn () => $this->fromWarehouse->code
                ),
            ],

            'to_warehouse' => [
                'id' => $this->to_warehouse_id,
                'name' => $this->whenLoaded(
                    'toWarehouse',
                    fn () => $this->toWarehouse->name
                ),
                'code' => $this->whenLoaded(
                    'toWarehouse',
                    fn () => $this->toWarehouse->code
                ),
            ],

            'items' => StockTransferItemResource::collection(
                $this->whenLoaded('items')
            ),

            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),

            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),

            'receiver' => $this->whenLoaded('receiver', fn () => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
            ]),

            'canceller' => $this->whenLoaded('canceller', fn () => [
                'id' => $this->canceller->id,
                'name' => $this->canceller->name,
            ]),

            'approved_at' => $this->approved_at,
            'received_at' => $this->received_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
