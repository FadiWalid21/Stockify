<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Product\Models\Product;
use App\Domains\Warehouse\Models\Warehouse;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;

class InventoryReportService
{
    public function summary(): array
    {
        $balances = InventoryBalance::query();

        $totalQuantity = (clone $balances)->sum('quantity');

        $productsWithStock = (clone $balances)
            ->where('quantity', '>', 0)
            ->distinct('product_id')
            ->count('product_id');

        $productsWithoutPositiveStock = Product::query()
            ->whereDoesntHave('inventoryBalances', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->count();

        return [
            'total_quantity' => (string) $totalQuantity,
            'products_with_stock' => $productsWithStock,
            'products_without_positive_stock' => $productsWithoutPositiveStock,
        ];
    }

    public function byWarehouse(): array
    {
        $balances = InventoryBalance::query()
            ->select('company_id', 'warehouse_id')
            ->selectRaw('
                COALESCE(SUM(quantity), 0) AS total_quantity
            ')
            ->selectRaw('
                COUNT(DISTINCT CASE
                    WHEN quantity > 0 THEN product_id
                    ELSE NULL
                END) AS product_count
            ')
            ->groupBy('company_id', 'warehouse_id');

        return Warehouse::query()
            ->leftJoinSub(
                $balances,
                'inventory_totals',
                function ($join) {
                    $join->on(
                        'warehouses.id',
                        '=',
                        'inventory_totals.warehouse_id'
                    )->on(
                        'warehouses.company_id',
                        '=',
                        'inventory_totals.company_id'
                    );
                }
            )
            ->select([
                'warehouses.id',
                'warehouses.name',
            ])
            ->selectRaw(
                'COALESCE(inventory_totals.total_quantity, 0) AS total_quantity'
            )
            ->selectRaw(
                'COALESCE(inventory_totals.product_count, 0) AS product_count'
            )
            ->orderBy('warehouses.name')
            ->get()
            ->map(function ($warehouse) {
                return [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'total_quantity' => (string) $warehouse->total_quantity,
                    'product_count' => (int) $warehouse->product_count,
                ];
            })
            ->toArray();
    }

    public function products(array $filters = [])
    {
        $query = InventoryBalance::query()
            ->with([
                'product.category',
                'product.unit',
                'warehouse',
            ]);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->whereHas('product', function ($productQuery) use ($search) {
                $productQuery->where(function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%");
                });
            });
        }

        if (isset($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->where('quantity', '>', 0);
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where('quantity', '<=', 0);
            }
        }

        $query = QueryFilter::apply(
            query: $query,
            params: $filters,
            filterableFields: [
                'product_id',
                'warehouse_id',
            ],
            sortableFields: [
                'quantity',
                'created_at',
            ],
        );

        if (empty($filters['sort_by'])) {
            $query->orderBy('quantity', 'asc');
        }

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }
}