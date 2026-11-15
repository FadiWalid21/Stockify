<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'product' => [
                'id' => $this->product_id,
                'name' => $this->whenLoaded(
                    'product',
                    fn () => $this->product->name
                ),
                'sku' => $this->whenLoaded(
                    'product',
                    fn () => $this->product->sku
                ),
            ],

            'quantity' => $this->quantity,
        ];
    }
}