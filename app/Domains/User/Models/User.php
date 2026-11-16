<?php

namespace App\Domains\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Domains\AccessControl\Models\Role;
use App\Domains\Company\Models\Company;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\Purchase\Models\PurchaseOrderReceipt;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesOrderFulfillment;
use App\Domains\User\Notifications\ResetPasswordNotification;
use App\Domains\User\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company() : BelongsTo {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        // The Owner has access to everything.
        if ($this->roles()->where('key', 'owner')->exists()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('key', $permission);
            })
            ->exists();
    }


    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function createdPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id');
    }

    public function approvedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'approved_by');
    }

    public function cancelledPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'cancelled_by');
    }

    public function purchaseOrderReceipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class, 'user_id');
    }

    public function createdSalesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'user_id');
    }

    public function confirmedSalesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'confirmed_by');
    }

    public function cancelledSalesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'cancelled_by');
    }

    public function salesOrderFulfillments(): HasMany
    {
        return $this->hasMany(SalesOrderFulfillment::class);
    }
}
