<?php

namespace App\Domains\Warehouse\Services;

use App\Domains\Warehouse\Models\Warehouse;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function getAll(array $params = []): LengthAwarePaginator
    {
        $query = Warehouse::query();

        $status = $params['status'] ?? 'active';

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        QueryFilter::apply(
            $query,
            $params,
            searchableFields: ['name', 'code', 'address'],
            sortableFields: ['name', 'code', 'created_at'],
        );

        if (! isset($params['sort_by'])) {
            $query->latest();
        }

        return $query->paginate(
            Pagination::perPage($params['per_page'] ?? null)
        );
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->refresh();
    }

    public function deactivate(Warehouse $warehouse): Warehouse
    {
        $this->ensureCanDeactivate($warehouse);

        $warehouse->update(['is_active' => false]);

        return $warehouse->refresh();
    }

    public function activate(Warehouse $warehouse): Warehouse
    {
        $warehouse->update(['is_active' => true]);

        return $warehouse->refresh();
    }

    public function delete(Warehouse $warehouse): void
    {
        if (
            $warehouse->inventoryBalances()->exists() ||
            $warehouse->stockMovements()->exists()
        ) {
            throw ValidationException::withMessages([
                'warehouse' => [
                    'This warehouse cannot be deleted because it has inventory records or stock movement history. Deactivate it instead.',
                ],
            ]);
        }

        $warehouse->delete();
    }

    private function ensureCanDeactivate(Warehouse $warehouse): void
    {
        // We'll add additional business rules here when implementing
        // warehouse transfers and inventory operations.
    }
}