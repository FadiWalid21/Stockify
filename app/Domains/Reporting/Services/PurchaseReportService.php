<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\Purchase\Models\PurchaseOrderItem;
use App\Domains\Purchase\Models\PurchaseOrderReceiptItem;
use Illuminate\Validation\ValidationException;

class PurchaseReportService
{
    /**
     * Purchase summary grouped by order status.
     */
    
    public function summary(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $statuses = [
            PurchaseOrderStatus::DRAFT->value,
            PurchaseOrderStatus::APPROVED->value,
            PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
            PurchaseOrderStatus::RECEIVED->value,
            PurchaseOrderStatus::CANCELLED->value,
        ];

        $orderedTotals = PurchaseOrderItem::query()
            ->select('company_id', 'purchase_order_id')
            ->selectRaw('
                COALESCE(SUM(quantity), 0) AS ordered_quantity,
                COALESCE(SUM(quantity * unit_cost), 0) AS ordered_value
            ')
            ->groupBy('company_id', 'purchase_order_id');

        $receivedTotals = PurchaseOrderReceiptItem::query()
            ->join(
                'purchase_order_items',
                'purchase_order_items.id',
                '=',
                'purchase_order_receipt_items.purchase_order_item_id'
            )
            ->whereColumn(
                'purchase_order_items.company_id',
                'purchase_order_receipt_items.company_id'
            )
            ->whereColumn(
                'purchase_order_items.purchase_order_id',
                'purchase_order_receipt_items.purchase_order_id'
            )
            ->select(
                'purchase_order_receipt_items.company_id',
                'purchase_order_receipt_items.purchase_order_id'
            )
            ->selectRaw('
                COALESCE(
                    SUM(purchase_order_receipt_items.quantity_received),
                    0
                ) AS received_quantity,
                COALESCE(
                    SUM(
                        purchase_order_receipt_items.quantity_received
                        * purchase_order_items.unit_cost
                    ),
                    0
                ) AS received_value
            ')
            ->groupBy(
                'purchase_order_receipt_items.company_id',
                'purchase_order_receipt_items.purchase_order_id'
            );

        $query = PurchaseOrder::query()
            ->leftJoinSub(
                $orderedTotals,
                'ordered_totals',
                function ($join) {
                    $join->on(
                        'purchase_orders.id',
                        '=',
                        'ordered_totals.purchase_order_id'
                    )->on(
                        'purchase_orders.company_id',
                        '=',
                        'ordered_totals.company_id'
                    );
                }
            )
            ->leftJoinSub(
                $receivedTotals,
                'received_totals',
                function ($join) {
                    $join->on(
                        'purchase_orders.id',
                        '=',
                        'received_totals.purchase_order_id'
                    )->on(
                        'purchase_orders.company_id',
                        '=',
                        'received_totals.company_id'
                    );
                }
            )
            ->whereBetween('purchase_orders.order_date', [$from, $to]);

        if (! empty($filters['supplier_id'])) {
            $query->where(
                'purchase_orders.supplier_id',
                $filters['supplier_id']
            );
        }

        $summary = $query
            ->select('purchase_orders.status')
            ->selectRaw('
                COUNT(*) AS order_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?, ?)
                            THEN COALESCE(ordered_totals.ordered_quantity, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS ordered_quantity,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?, ?)
                            THEN COALESCE(ordered_totals.ordered_value, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS ordered_value,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?, ?)
                            THEN COALESCE(received_totals.received_quantity, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS received_quantity,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?, ?)
                            THEN COALESCE(received_totals.received_value, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS received_value,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?)
                            THEN GREATEST(
                                COALESCE(ordered_totals.ordered_quantity, 0)
                                - COALESCE(received_totals.received_quantity, 0),
                                0
                            )
                            ELSE 0
                        END
                    ),
                    0
                ) AS pending_quantity,

                COALESCE(
                    SUM(
                        CASE
                            WHEN purchase_orders.status IN (?, ?)
                            THEN GREATEST(
                                COALESCE(ordered_totals.ordered_value, 0)
                                - COALESCE(received_totals.received_value, 0),
                                0
                            )
                            ELSE 0
                        END
                    ),
                    0
                ) AS pending_value
            ', [
                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                PurchaseOrderStatus::RECEIVED->value,

                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                PurchaseOrderStatus::RECEIVED->value,

                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                PurchaseOrderStatus::RECEIVED->value,

                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,

                PurchaseOrderStatus::APPROVED->value,
                PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
            ])
            ->groupBy('purchase_orders.status')
            ->get()
            ->keyBy('status');

        $statusReport = [];

        foreach ($statuses as $status) {
            $row = $summary->get($status);

            $statusReport[$status] = [
                'order_count' => (int) ($row->order_count ?? 0),
                'ordered_quantity' => (string) ($row->ordered_quantity ?? '0'),
                'ordered_value' => (string) ($row->ordered_value ?? '0'),
                'received_quantity' => (string) ($row->received_quantity ?? '0'),
                'received_value' => (string) ($row->received_value ?? '0'),
                'pending_quantity' => (string) ($row->pending_quantity ?? '0'),
                'pending_value' => (string) ($row->pending_value ?? '0'),
            ];
        }

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'statuses' => $statusReport,
        ];
    }

    /**
     * Validate and resolve reporting period.
     */
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