<?php

namespace App\Domains\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanySetting extends Model
{
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

    public function setting(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }
}