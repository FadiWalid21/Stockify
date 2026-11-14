<?php

namespace App\Domains\Company\Scopes;

use App\Domains\Company\Contracts\TenantContextInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        if ($companyId === null) {
            // Fail closed: never return records without a tenant context.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where(
            $model->qualifyColumn('company_id'),
            $companyId
        );
    }
}