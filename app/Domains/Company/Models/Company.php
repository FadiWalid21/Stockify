<?php

namespace App\Domains\Company\Models;

use App\Domains\AccessControl\Models\Role;
use App\Domains\Category\Models\Category;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone', 'address'])]
class Company extends Model
{
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function warehouses(): HasMany
    {
    return $this->hasMany(Warehouse::class);
    }

    public function roles(): HasMany
{
    return $this->hasMany(Role::class);
}
}