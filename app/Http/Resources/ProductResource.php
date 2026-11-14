<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'category' => $this->whenLoaded(
                'category',
                fn () => new CategoryResource($this->category)
            ),

            'unit' => $this->whenLoaded(
                'unit',
                fn () => new UnitResource($this->unit)
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}