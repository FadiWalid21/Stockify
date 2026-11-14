<?php

namespace App\Domains\Category\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\TenantModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[Fillable(['name', 'description'])]
class Category extends TenantModel
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}