<?php

namespace App\Domains\Unit\Services;

use App\Domains\Unit\Models\Unit;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitService
{
    public function getAll(array $params = []): LengthAwarePaginator
    {
        $query = Unit::query();

        QueryFilter::apply(
            $query,
            $params,
            searchableFields: ['name', 'symbol'],
            sortableFields: ['name', 'symbol', 'created_at'],
        );

        if (! isset($params['sort_by'])) {
            $query->orderBy('name');
        }

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }
}