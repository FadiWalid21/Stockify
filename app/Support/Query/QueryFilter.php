<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;

class QueryFilter
{
    public static function apply(
        Builder $query,
        array $params,
        array $searchableFields = [],
        array $filterableFields = [],
        array $sortableFields = [],
    ): Builder {
        // 1. Search across allowed fields.
        $search = trim($params['search'] ?? '');

        if ($search !== '' && ! empty($searchableFields)) {
            $query->where(function (Builder $query) use (
                $search,
                $searchableFields
            ) {
                foreach ($searchableFields as $field) {
                    $query->orWhere(
                        $field,
                        'ILIKE',
                        "%{$search}%"
                    );
                }
            });
        }

        // 2. Apply exact-match filters.
        foreach ($filterableFields as $field) {
            if (
                array_key_exists($field, $params) &&
                $params[$field] !== null &&
                $params[$field] !== ''
            ) {
                $query->where($field, $params[$field]);
            }
        }

        // 3. Apply sorting.
        $sortBy = $params['sort_by'] ?? null;

        if (
            $sortBy !== null &&
            in_array($sortBy, $sortableFields, true)
        ) {
            $direction = strtolower(
                $params['sort_direction'] ?? 'asc'
            );

            if (! in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'asc';
            }

            $query->orderBy($sortBy, $direction);
        }

        return $query;
    }
}
