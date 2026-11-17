<?php

namespace App\Console\Commands;

use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesPayment;
use Illuminate\Console\Command;

class MarkOverdueSalesInvoices extends Command
{
    protected $signature = 'app:mark-overdue-sales-invoices';

    protected $description = 'Automatically mark unpaid overdue sales invoices';

    public function handle(): int
    {
        $updated = SalesInvoice::query()
            ->whereIn('status', [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
            ])
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereRaw(
                'sales_invoices.grand_total > COALESCE(
                    (
                        SELECT SUM(sales_payment_allocations.amount)
                        FROM sales_payment_allocations
                        INNER JOIN sales_payments
                            ON sales_payments.id = sales_payment_allocations.sales_payment_id
                            AND sales_payments.company_id = sales_payment_allocations.company_id
                        WHERE sales_payment_allocations.company_id = sales_invoices.company_id
                            AND sales_payment_allocations.sales_invoice_id = sales_invoices.id
                            AND sales_payments.status = ?
                    ),
                    0
                )',
                [SalesPayment::STATUS_COMPLETED]
            )
            ->update([
                'status' => SalesInvoice::STATUS_OVERDUE,
            ]);

        $this->info("Marked {$updated} sales invoice(s) as overdue.");

        return self::SUCCESS;
    }
}