<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\Purchase\Models\PurchaseOrderItem;
use App\Domains\Purchase\Models\PurchaseOrderReceiptItem;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Validation\ValidationException;

class SupplierReportService
{
    public function purchaseSummary(array $filters = [])
    {
        if (
            ! empty($filters['from']) &&
            ! empty($filters['to']) &&
            $filters['from'] > $filters['to']
        ) {
            throw ValidationException::withMessages([
                'from' => 'The start date must be before or equal to the end date.',
            ]);
        }

        $itemTotals = PurchaseOrderItem::query()
            ->select('purchase_order_id', 'company_id')
            ->selectRaw('SUM(quantity) AS ordered_quantity')
            ->selectRaw('SUM(quantity * unit_cost) AS ordered_value')
            ->groupBy('purchase_order_id', 'company_id');

        $receiptTotals = PurchaseOrderReceiptItem::query()
            ->join('purchase_order_items', function ($join) {
                $join->on(
                    'purchase_order_items.id',
                    '=',
                    'purchase_order_receipt_items.purchase_order_item_id'
                )->on(
                    'purchase_order_items.company_id',
                    '=',
                    'purchase_order_receipt_items.company_id'
                );
            })
            ->select(
                'purchase_order_receipt_items.purchase_order_id',
                'purchase_order_receipt_items.company_id'
            )
            ->selectRaw(
                'SUM(purchase_order_receipt_items.quantity_received) AS received_quantity'
            )
            ->selectRaw(
                'SUM(
                    purchase_order_receipt_items.quantity_received
                    * purchase_order_items.unit_cost
                ) AS received_value'
            )
            ->groupBy(
                'purchase_order_receipt_items.purchase_order_id',
                'purchase_order_receipt_items.company_id'
            );

        $query = PurchaseOrder::query()
            ->join('suppliers', function ($join) {
                $join->on(
                    'suppliers.id',
                    '=',
                    'purchase_orders.supplier_id'
                )->on(
                    'suppliers.company_id',
                    '=',
                    'purchase_orders.company_id'
                );
            })
            ->leftJoinSub(
                $itemTotals,
                'item_totals',
                function ($join) {
                    $join->on(
                        'item_totals.purchase_order_id',
                        '=',
                        'purchase_orders.id'
                    )->on(
                        'item_totals.company_id',
                        '=',
                        'purchase_orders.company_id'
                    );
                }
            )
            ->leftJoinSub(
                $receiptTotals,
                'receipt_totals',
                function ($join) {
                    $join->on(
                        'receipt_totals.purchase_order_id',
                        '=',
                        'purchase_orders.id'
                    )->on(
                        'receipt_totals.company_id',
                        '=',
                        'purchase_orders.company_id'
                    );
                }
            )
            ->whereIn('purchase_orders.status', [
                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                PurchaseOrderStatus::RECEIVED->value,
            ])
            ->select([
                'suppliers.id as supplier_id',
                'suppliers.name as supplier_name',
            ])
            ->selectRaw(
                'COUNT(purchase_orders.id) AS order_count'
            )
            ->selectRaw(
                'COALESCE(SUM(item_totals.ordered_value), 0) AS ordered_value'
            )
            ->selectRaw(
                'COALESCE(SUM(receipt_totals.received_value), 0) AS received_value'
            )
            ->selectRaw(
                'COALESCE(SUM(item_totals.ordered_quantity), 0) AS ordered_quantity'
            )
            ->selectRaw(
                'COALESCE(SUM(receipt_totals.received_quantity), 0) AS received_quantity'
            )
            ->groupBy(
                'suppliers.id',
                'suppliers.name'
            );

        if (! empty($filters['from'])) {
            $query->whereDate(
                'purchase_orders.order_date',
                '>=',
                $filters['from']
            );
        }

        if (! empty($filters['to'])) {
            $query->whereDate(
                'purchase_orders.order_date',
                '<=',
                $filters['to']
            );
        }

        if (! empty($filters['supplier_id'])) {
            $query->where(
                'purchase_orders.supplier_id',
                $filters['supplier_id']
            );
        }

        $query = QueryFilter::apply(
            query: $query,
            params: $filters,
            searchableFields: [
                'suppliers.name',
            ],
            sortableFields: [
                'supplier_name',
                'order_count',
                'ordered_value',
                'received_value',
                'ordered_quantity',
                'received_quantity',
            ],
        );

        if (empty($filters['sort_by'])) {
            $query->orderByDesc('ordered_value');
        }

        $query->orderBy('suppliers.id');

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }
}