<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Product\Models\Product;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class InventoryBalance extends TenantModel
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}