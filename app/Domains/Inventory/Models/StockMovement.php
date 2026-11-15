<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Inventory\Enums\StockMovementType;
use App\Domains\Product\Models\Product;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends TenantModel
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'inventory_operation_id',
        'quantity',
        'type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'type' => StockMovementType::class,
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

    public function inventoryOperation(): BelongsTo
    {
        return $this->belongsTo(InventoryOperation::class);
    }
}
