<?php

namespace App\Domains\Warehouse\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['name', 'code', 'address', 'is_active'])]
class Warehouse extends TenantModel
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}