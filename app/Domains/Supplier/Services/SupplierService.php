<?php

namespace App\Domains\Supplier\Services;

use App\Domains\Supplier\Models\Supplier;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function getSuppliers(array $params = []): LengthAwarePaginator
    {
        $query = Supplier::query();

        $query = QueryFilter::apply(
            $query,
            $params,
            searchableFields: ['name', 'email', 'phone', 'tax_number', 'contact_person'],
            filterableFields: [],
            sortableFields: ['name', 'email', 'created_at'],
        );

        $status = $params['status'] ?? 'active';

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (empty($params['sort_by'])) {
            $query->latest();
        }

        return $query->paginate(
            Pagination::perPage(isset($params['per_page']) ? (int) $params['per_page'] : null)
        );
    }

    public function createSupplier(array $data): Supplier
    {
        return Supplier::query()->create($data);
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->refresh();
    }

    public function deleteSupplier(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function activateSupplier(Supplier $supplier): Supplier
    {
        $supplier->update(['is_active' => true]);

        return $supplier->refresh();
    }

    public function deactivateSupplier(Supplier $supplier): Supplier
    {
        $supplier->update(['is_active' => false]);

        return $supplier->refresh();
    }
}
