<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesInvoiceItem;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Validation\ValidationException;

class ProductPerformanceReportService
{
    public function topSelling(array $filters = [])
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

        $query = SalesInvoiceItem::query()
            ->join('sales_invoices', function ($join) {
                $join->on(
                    'sales_invoices.id',
                    '=',
                    'sales_invoice_items.sales_invoice_id'
                )->on(
                    'sales_invoices.company_id',
                    '=',
                    'sales_invoice_items.company_id'
                );
            })
            ->join('products', function ($join) {
                $join->on(
                    'products.id',
                    '=',
                    'sales_invoice_items.product_id'
                )->on(
                    'products.company_id',
                    '=',
                    'sales_invoice_items.company_id'
                );
            })
            ->whereIn('sales_invoices.status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ])
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
            ])
            ->selectRaw(
                'SUM(sales_invoice_items.quantity) AS quantity_sold'
            )
            ->selectRaw(
                'SUM(sales_invoice_items.line_subtotal) AS sales_value'
            )
            ->selectRaw(
                'SUM(sales_invoice_items.line_total) AS total_value'
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.sku'
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

        if (! empty($filters['product_id'])) {
            $query->where(
                'sales_invoice_items.product_id',
                $filters['product_id']
            );
        }

        $query = QueryFilter::apply(
            query: $query,
            params: $filters,
            searchableFields: [
                'products.name',
                'products.sku',
            ],
            filterableFields: [],
            sortableFields: [
                'quantity_sold',
                'sales_value',
                'total_value',
                'product_name',
            ],
        );

        if (empty($filters['sort_by'])) {
            $query->orderByDesc('quantity_sold');
        }

        $query->orderBy('products.id');

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }
}