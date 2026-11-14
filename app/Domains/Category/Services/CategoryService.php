<?php

namespace App\Domains\Category\Services;

use App\Domains\Category\Models\Category;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function getAll(array $params = []): LengthAwarePaginator
    {
        $query = Category::query();

        QueryFilter::apply(
            $query,
            $params,
            searchableFields: ['name', 'description'],
            filterableFields: [],
            sortableFields: ['name', 'created_at'],
        );

        if (! isset($params['sort_by'])) {
            $query->latest();
        }

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => [
                    'This category cannot be deleted because it has products assigned to it.',
                ],
            ]);
        }

        $category->delete();
    }
}
