<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Customer\Models\Customer;
use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesPayment;
use App\Domains\Sales\Models\SalesPaymentAllocation;
use App\Support\Query\Pagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Domains\Sales\Models\SalesInvoiceItem;
use App\Domains\Returns\Enums\SalesReturnStatus;
use App\Domains\Returns\Models\SalesReturn;
use App\Domains\Returns\Models\SalesReturnItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class SalesReportService
{
    /**
     * Sales revenue and collection summary.
     */
    public function revenue(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $invoiceQuery = SalesInvoice::query()
            ->whereIn('status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->whereBetween('invoice_date', [$from, $to]);

        if (! empty($filters['customer_id'])) {
            $invoiceQuery->where('customer_id', $filters['customer_id']);
        }

        $invoiceSummary = (clone $invoiceQuery)
            ->selectRaw('
                COUNT(*) AS invoice_count,
                COALESCE(SUM(grand_total), 0) AS invoiced_revenue,
                COALESCE(AVG(grand_total), 0) AS average_invoice_value
            ')
            ->first();

        $collectionQuery = SalesPayment::query()
            ->where('status', SalesPayment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from, $to]);

        if (! empty($filters['customer_id'])) {
            $collectionQuery->where(
                'customer_id',
                $filters['customer_id']
            );
        }

        $collections = $collectionQuery->sum('amount');

        $outstandingQuery = SalesInvoice::query()
            ->whereIn('status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ]);

        if (! empty($filters['customer_id'])) {
            $outstandingQuery->where(
                'customer_id',
                $filters['customer_id']
            );
        }

        $outstanding = $this->outstandingQuery($outstandingQuery)
            ->sum(DB::raw(
                'sales_invoices.grand_total - COALESCE(payment_totals.amount_paid, 0)'
            ));

        $dailySales = (clone $invoiceQuery)
            ->selectRaw('
                invoice_date,
                COUNT(*) AS invoice_count,
                COALESCE(SUM(grand_total), 0) AS revenue
            ')
            ->groupBy('invoice_date')
            ->orderBy('invoice_date')
            ->get();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'invoice_count' => (int) $invoiceSummary->invoice_count,
                'invoiced_revenue' => (string) $invoiceSummary->invoiced_revenue,
                'payments_collected' => (string) $collections,
                'outstanding_balance' => (string) $outstanding,
                'average_invoice_value' => (string) $invoiceSummary->average_invoice_value,
            ],
            'daily_sales' => $dailySales,
        ];
    }

    /**
     * Sales performance grouped by customer.
     */
    public function byCustomer(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $query = Customer::query()
            ->select('customers.id', 'customers.name')
            ->selectSub(
                SalesInvoice::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'sales_invoices.customer_id',
                        'customers.id'
                    )
                    ->whereIn('status', [
                        SalesInvoice::STATUS_ISSUED,
                        SalesInvoice::STATUS_PARTIALLY_PAID,
                        SalesInvoice::STATUS_PAID,
                        SalesInvoice::STATUS_OVERDUE,
                    ])
                    ->whereBetween('invoice_date', [$from, $to]),
                'invoice_count'
            )
            ->selectSub(
                SalesInvoice::query()
                    ->selectRaw('COALESCE(SUM(grand_total), 0)')
                    ->whereColumn(
                        'sales_invoices.customer_id',
                        'customers.id'
                    )
                    ->whereIn('status', [
                        SalesInvoice::STATUS_ISSUED,
                        SalesInvoice::STATUS_PARTIALLY_PAID,
                        SalesInvoice::STATUS_PAID,
                        SalesInvoice::STATUS_OVERDUE,
                    ])
                    ->whereBetween('invoice_date', [$from, $to]),
                'invoiced_sales'
            )
            ->selectSub(
                SalesPayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn(
                        'sales_payments.customer_id',
                        'customers.id'
                    )
                    ->where('status', SalesPayment::STATUS_COMPLETED)
                    ->whereBetween('payment_date', [$from, $to]),
                'payments_collected'
            )
            ->selectSub(
                SalesInvoice::query()
                    ->whereIn('status', [
                        SalesInvoice::STATUS_ISSUED,
                        SalesInvoice::STATUS_PARTIALLY_PAID,
                        SalesInvoice::STATUS_OVERDUE,
                    ])
                    ->whereColumn(
                        'sales_invoices.customer_id',
                        'customers.id'
                    )
                    ->leftJoinSub(
                        SalesPaymentAllocation::query()
                            ->select(
                                'sales_invoice_id',
                                DB::raw('SUM(amount) AS amount_paid')
                            )
                            ->whereHas('payment', function ($paymentQuery) {
                                $paymentQuery->where(
                                    'status',
                                    SalesPayment::STATUS_COMPLETED
                                );
                            })
                            ->groupBy('sales_invoice_id'),
                        'payment_totals',
                        'sales_invoices.id',
                        '=',
                        'payment_totals.sales_invoice_id'
                    )
                    ->selectRaw('
                        COALESCE(
                            SUM(
                                sales_invoices.grand_total
                                - COALESCE(payment_totals.amount_paid, 0)
                            ),
                            0
                        )
                    '),
                'outstanding_balance'
            );

        if (! empty($filters['customer_id'])) {
            $query->where('customers.id', $filters['customer_id']);
        }

        $query->orderByDesc('invoiced_sales')
            ->orderBy('customers.name');

        $perPage = Pagination::perPage(
            isset($filters['per_page'])
                ? (int) $filters['per_page']
                : null
        );

        $customers = $query->paginate($perPage);

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'customers' => $customers,
        ];
    }

        /**
     * Sales performance grouped by product.
     */

    public function byProduct(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $query = SalesInvoiceItem::query()
            ->join(
                'sales_invoices',
                'sales_invoices.id',
                '=',
                'sales_invoice_items.sales_invoice_id'
            )
            ->join(
                'products',
                'products.id',
                '=',
                'sales_invoice_items.product_id'
            )
            ->whereColumn(
                'sales_invoices.company_id',
                'sales_invoice_items.company_id'
            )
            ->whereColumn(
                'products.company_id',
                'sales_invoice_items.company_id'
            )
            ->whereIn('sales_invoices.status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->whereBetween('sales_invoices.invoice_date', [$from, $to])
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku'
            )
            ->selectRaw('
                COUNT(DISTINCT sales_invoices.id) AS invoice_count,
                COALESCE(SUM(sales_invoice_items.quantity), 0) AS quantity_sold,
                COALESCE(SUM(sales_invoice_items.line_total), 0) AS sales_value
            ')
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku'
            )
            ->orderByDesc('sales_value');

        if (! empty($filters['customer_id'])) {
            $query->where(
                'sales_invoices.customer_id',
                $filters['customer_id']
            );
        }

        $products = $query->paginate(
            Pagination::perPage(
                isset($filters['per_page'])
                    ? (int) $filters['per_page']
                    : null
            )
        );

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'products' => $products,
        ];
    }

        /**
     * Sales invoice summary grouped by status.
     */
    
    public function invoiceStatus(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $statuses = [
            SalesInvoice::STATUS_DRAFT,
            SalesInvoice::STATUS_ISSUED,
            SalesInvoice::STATUS_PARTIALLY_PAID,
            SalesInvoice::STATUS_PAID,
            SalesInvoice::STATUS_OVERDUE,
            SalesInvoice::STATUS_CANCELLED,
        ];

        $query = SalesInvoice::query()
            ->whereBetween('invoice_date', [$from, $to]);

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $paymentTotals = SalesPaymentAllocation::query()
            ->select(
                'sales_invoice_id',
                DB::raw('SUM(amount) AS amount_paid')
            )
            ->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where(
                    'status',
                    SalesPayment::STATUS_COMPLETED
                );
            })
            ->groupBy('sales_invoice_id');

        $summary = $query
            ->leftJoinSub(
                $paymentTotals,
                'payment_totals',
                'sales_invoices.id',
                '=',
                'payment_totals.sales_invoice_id'
            )
            ->select('sales_invoices.status')
            ->selectRaw('
                COUNT(*) AS invoice_count,
                COALESCE(SUM(sales_invoices.grand_total), 0) AS invoice_value,
                COALESCE(SUM(payment_totals.amount_paid), 0) AS amount_collected,
                COALESCE(
                    SUM(
                        CASE
                            WHEN sales_invoices.status IN (?, ?, ?)
                            THEN sales_invoices.grand_total
                                - COALESCE(payment_totals.amount_paid, 0)
                            ELSE 0
                        END
                    ),
                    0
                ) AS outstanding_balance
            ', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->groupBy('sales_invoices.status')
            ->get()
            ->keyBy('status');

        $statusesReport = [];

        foreach ($statuses as $status) {
            $row = $summary->get($status);

            $statusesReport[$status] = [
                'invoice_count' => (int) ($row->invoice_count ?? 0),
                'invoice_value' => (string) ($row->invoice_value ?? '0'),
                'amount_collected' => (string) ($row->amount_collected ?? '0'),
                'outstanding_balance' => (string) (
                    $row->outstanding_balance ?? '0'
                ),
            ];
        }

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'statuses' => $statusesReport,
        ];
    }
        /**
     * Sales trend grouped by day or month.
     */
    public function salesTrend(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $groupBy = $filters['group_by'] ?? 'day';

        $dateExpression = $groupBy === 'month'
            ? "TO_CHAR(invoice_date, 'YYYY-MM')"
            : "TO_CHAR(invoice_date, 'YYYY-MM-DD')";

        $query = SalesInvoice::query()
            ->whereIn('status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->whereBetween('invoice_date', [$from, $to]);

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $trend = $query
            ->selectRaw("
                {$dateExpression} AS period,
                COUNT(*) AS invoice_count,
                COALESCE(SUM(grand_total), 0) AS invoiced_sales,
                COALESCE(AVG(grand_total), 0) AS average_invoice_value
            ")
            ->groupByRaw($dateExpression)
            ->orderByRaw('period ASC')
            ->get();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'group_by' => $groupBy,
            'trend' => $trend,
        ];
    }

        /**
     * Sales returns summary grouped by status.
     */
    
    public function salesReturns(array $filters = []): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        $statuses = [
            SalesReturnStatus::DRAFT->value,
            SalesReturnStatus::APPROVED->value,
            SalesReturnStatus::RECEIVED->value,
            SalesReturnStatus::COMPLETED->value,
            SalesReturnStatus::CANCELLED->value,
        ];

        $itemsQuery = SalesReturnItem::query()
            ->select('sales_return_id')
            ->selectRaw('SUM(quantity) AS returned_quantity')
            ->groupBy('sales_return_id');

        $query = SalesReturn::query()
            ->leftJoinSub(
                $itemsQuery,
                'return_item_totals',
                'sales_returns.id',
                '=',
                'return_item_totals.sales_return_id'
            )
            ->whereBetween('sales_returns.return_date', [$from, $to]);

        if (! empty($filters['customer_id'])) {
            $query->where(
                'sales_returns.customer_id',
                $filters['customer_id']
            );
        }

        $summary = $query
            ->select('sales_returns.status')
            ->selectRaw('
                COUNT(*) AS return_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN sales_returns.status IN (?, ?)
                            THEN return_item_totals.returned_quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS returned_quantity,

                COALESCE(
                    SUM(
                        CASE
                            WHEN sales_returns.status IN (?, ?)
                            THEN sales_returns.subtotal
                            ELSE 0
                        END
                    ),
                    0
                ) AS returned_value
            ', [
                SalesReturnStatus::RECEIVED->value,
                SalesReturnStatus::COMPLETED->value,
                SalesReturnStatus::RECEIVED->value,
                SalesReturnStatus::COMPLETED->value,
            ])
            ->groupBy('sales_returns.status')
            ->get()
            ->keyBy('status');

        $statusReport = [];

        foreach ($statuses as $status) {
            $row = $summary->get($status);

            $statusReport[$status] = [
                'return_count' => (int) ($row->return_count ?? 0),
                'returned_quantity' => (string) ($row->returned_quantity ?? '0'),
                'returned_value' => (string) ($row->returned_value ?? '0'),
            ];
        }

        $actualReturns = [
            $statusReport[SalesReturnStatus::RECEIVED->value],
            $statusReport[SalesReturnStatus::COMPLETED->value],
        ];

        $returnCount = 0;
        $returnedQuantity = BigDecimal::zero();
        $returnedValue = BigDecimal::zero();

        foreach ($actualReturns as $return) {
            $returnCount += $return['return_count'];

            $returnedQuantity = $returnedQuantity->plus(
                BigDecimal::of($return['returned_quantity'])
            );

            $returnedValue = $returnedValue->plus(
                BigDecimal::of($return['returned_value'])
            );
        }

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'return_count' => $returnCount,
                'returned_quantity' => (string) $returnedQuantity->toScale(
                    4,
                    RoundingMode::HalfUp
                ),
                'returned_value' => (string) $returnedValue->toScale(
                    4,
                    RoundingMode::HalfUp
                ),
            ],
            'statuses' => $statusReport,
        ];
    }

    /**
     * Apply completed payment allocations to an invoice query.
     */
    private function outstandingQuery($query)
    {
        return $query->leftJoinSub(
            SalesPaymentAllocation::query()
                ->select(
                    'sales_invoice_id',
                    DB::raw('SUM(amount) AS amount_paid')
                )
                ->whereHas('payment', function ($paymentQuery) {
                    $paymentQuery->where(
                        'status',
                        SalesPayment::STATUS_COMPLETED
                    );
                })
                ->groupBy('sales_invoice_id'),
            'payment_totals',
            'sales_invoices.id',
            '=',
            'payment_totals.sales_invoice_id'
        );
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