<?php

namespace App\Domains\Product\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Category\Models\Category;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Transfer\Models\StockTransferItem;
use App\Domains\Unit\Models\Unit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable([
    'name',
    'sku',
    'description',
    'category_id',
    'unit_id',
    'is_active',
])]
class Product extends TenantModel
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}