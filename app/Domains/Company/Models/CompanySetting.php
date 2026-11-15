<?php

namespace App\Domains\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    protected $fillable = [
        'allow_negative_stock',
    ];

    protected function casts(): array
    {
        return [
            'allow_negative_stock' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
