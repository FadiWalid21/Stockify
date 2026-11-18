<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesPayment;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Validation\ValidationException;

class CustomerReportService
{
    public function salesSummary(array $filters = [])
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

        $collectedAmountSql = "
            COALESCE(
                (
                    SELECT SUM(sales_payment_allocations.amount)
                    FROM sales_payment_allocations
                    INNER JOIN sales_payments
                        ON sales_payments.id = sales_payment_allocations.sales_payment_id
                        AND sales_payments.company_id = sales_payment_allocations.company_id
                    WHERE sales_payment_allocations.sales_invoice_id = sales_invoices.id
                        AND sales_payment_allocations.company_id = sales_invoices.company_id
                        AND sales_payments.status = ?
                ),
                0
            )
        ";

        $query = SalesInvoice::query()
            ->join('customers', function ($join) {
                $join->on(
                    'customers.id',
                    '=',
                    'sales_invoices.customer_id'
                )->on(
                    'customers.company_id',
                    '=',
                    'sales_invoices.company_id'
                );
            })
            ->whereIn('sales_invoices.status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
            ])
            ->selectRaw(
                'COUNT(sales_invoices.id) AS invoice_count'
            )
            ->selectRaw(
                'SUM(sales_invoices.grand_total) AS total_invoiced'
            )
            ->selectRaw(
                "SUM({$collectedAmountSql}) AS amount_collected",
                [SalesPayment::STATUS_COMPLETED]
            )
            ->selectRaw(
                "SUM(
                    GREATEST(
                        sales_invoices.grand_total - {$collectedAmountSql},
                        0
                    )
                ) AS outstanding_amount",
                [SalesPayment::STATUS_COMPLETED]
            )
            ->groupBy(
                'customers.id',
                'customers.name'
            );

        if (! empty($filters['from'])) {
            $query->whereDate(
                'sales_invoices.invoice_date',
                '>=',
                $filters['from']
            );
        }

        if (! empty($filters['to'])) {
            $query->whereDate(
                'sales_invoices.invoice_date',
                '<=',
                $filters['to']
            );
        }

        if (! empty($filters['customer_id'])) {
            $query->where(
                'sales_invoices.customer_id',
                $filters['customer_id']
            );
        }

        $query = QueryFilter::apply(
            query: $query,
            params: $filters,
            searchableFields: [
                'customers.name',
            ],
            sortableFields: [
                'customer_name',
                'invoice_count',
                'total_invoiced',
                'amount_collected',
                'outstanding_amount',
            ],
        );

        if (empty($filters['sort_by'])) {
            $query->orderByDesc('total_invoiced');
        }

        $query->orderBy('customers.id');

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }
}