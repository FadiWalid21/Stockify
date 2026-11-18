<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Inventory\Enums\InventoryOperationType;
use App\Domains\Inventory\Enums\StockMovementType;
use App\Domains\Inventory\Models\StockMovement;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Validation\ValidationException;

class StockMovementReportService
{
    public function summary(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $query = StockMovement::query()
            ->whereHas('inventoryOperation', function ($operationQuery) {
                $operationQuery->whereIn('type', [
                    InventoryOperationType::STOCK_IN->value,
                    InventoryOperationType::STOCK_OUT->value,
                    InventoryOperationType::OPENING_BALANCE->value,
                    InventoryOperationType::ADJUSTMENT->value,
                ]);
            })
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $summary = (clone $query)
            ->selectRaw(
                '
                    COUNT(*) AS movement_count,
                    COALESCE(
                        SUM(CASE WHEN type = ? THEN quantity ELSE 0 END),
                        0
                    ) AS total_in,
                    COALESCE(
                        SUM(CASE WHEN type = ? THEN quantity ELSE 0 END),
                        0
                    ) AS total_out
                ',
                [
                    StockMovementType::IN->value,
                    StockMovementType::OUT->value,
                ]
            )
            ->first();

        $adjustmentMovementCount = (clone $query)
            ->whereHas('inventoryOperation', function ($operationQuery) {
                $operationQuery->where(
                    'type',
                    InventoryOperationType::ADJUSTMENT->value
                );
            })
            ->count();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'movement_count' => (int) $summary->movement_count,
            'total_in' => (string) $summary->total_in,
            'total_out' => (string) $summary->total_out,
            'adjustment_movement_count' => $adjustmentMovementCount,
        ];
    }

    public function movements(array $filters = [])
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $query = StockMovement::query()
            ->with([
                'product',
                'warehouse',
                'inventoryOperation',
            ])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['operation_type'])) {
            $query->whereHas('inventoryOperation', function ($operationQuery) use ($filters) {
                $operationQuery->where(
                    'type',
                    $filters['operation_type']
                );
            });
        }

        $query = QueryFilter::apply(
            query: $query,
            params: $filters,
            sortableFields: [
                'created_at',
                'quantity',
            ],
        );

        if (empty($filters['sort_by'])) {
            $query->latest('created_at');
        }

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }

    private function resolvePeriod(array $filters): array
    {
        $from = $filters['from'] ?? now()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        if ($from > $to) {
            throw ValidationException::withMessages([
                'from' => 'The start date must be before or equal to the end date.',
            ]);
        }

        return [$from, $to];
    }
}