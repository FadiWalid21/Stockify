<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use App\Domains\Inventory\Enums\InventoryOperationType;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryOperation extends TenantModel
{
    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryOperationType::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
