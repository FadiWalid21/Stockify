<?php

namespace App\Domains\Transfer\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends TenantModel
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}