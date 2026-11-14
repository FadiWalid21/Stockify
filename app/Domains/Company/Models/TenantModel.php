<?php

namespace App\Domains\Company\Models;

use App\Domains\Company\Contracts\TenantContextInterface;
use App\Domains\Company\Exceptions\MissingTenantContextException;
use App\Domains\Company\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            $companyId = app(TenantContextInterface::class)->getCompanyId();

            if ($companyId === null) {
                throw new MissingTenantContextException();
            }

            $model->company_id = $companyId;
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('company_id')) {
                throw new \LogicException(
                    'Changing the company of an existing record is not allowed.'
                );
            }
        });
    }
}
