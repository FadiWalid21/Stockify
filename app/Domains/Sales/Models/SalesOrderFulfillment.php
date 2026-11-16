<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderFulfillment extends TenantModel
{
    protected $fillable = [
        'sales_order_id',
        'user_id',
        'dispatched_at',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderFulfillmentItem::class);
    }
}