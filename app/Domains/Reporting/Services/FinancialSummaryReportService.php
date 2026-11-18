<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class FinancialSummaryReportService
{
    public function __construct(
        private SalesReportService $salesReportService,
        private PurchaseReportService $purchaseReportService,
    ) {}

    /**
     * Build a financial summary from existing sales and purchase reports.
     */
    public function summary(array $filters = []): array
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

        $sales = $this->salesReportService->revenue($filters);

        $purchases = $this->purchaseReportService->summary($filters);

        $includedStatuses = [
            PurchaseOrderStatus::APPROVED->value,
            PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
            PurchaseOrderStatus::RECEIVED->value,
        ];

        $purchaseSummary = [
            'ordered_value' => '0.0000',
            'received_value' => '0.0000',
            'pending_value' => '0.0000',
            'order_count' => 0,
        ];

        foreach ($includedStatuses as $status) {
            $row = $purchases['statuses'][$status] ?? [];

            $purchaseSummary['ordered_value'] = $this->addAmounts(
                $purchaseSummary['ordered_value'],
                (string) ($row['ordered_value'] ?? '0')
            );

            $purchaseSummary['received_value'] = $this->addAmounts(
                $purchaseSummary['received_value'],
                (string) ($row['received_value'] ?? '0')
            );

            $purchaseSummary['pending_value'] = $this->addAmounts(
                $purchaseSummary['pending_value'],
                (string) ($row['pending_value'] ?? '0')
            );

            $purchaseSummary['order_count'] += (int) ($row['order_count'] ?? 0);
        }

        return [
            'period' => $sales['period'],

            'summary' => [
                'invoiced_revenue' => $sales['summary']['invoiced_revenue'],
                'payments_collected' => $sales['summary']['payments_collected'],
                'outstanding_receivables' => $sales['summary']['outstanding_balance'],

                'purchases_ordered' => $purchaseSummary['ordered_value'],
                'purchases_received' => $purchaseSummary['received_value'],
                'pending_purchase_value' => $purchaseSummary['pending_value'],

                'purchase_order_count' => $purchaseSummary['order_count'],
            ],

            'sales' => $sales,

            'purchases' => [
                'summary' => $purchaseSummary,
                'statuses' => $purchases['statuses'],
            ],
        ];
    }

    /**
     * Add decimal amounts without using floating-point arithmetic.
     */
    private function addAmounts(string $first, string $second): string
    {
        return (string) BigDecimal::of($first)
            ->plus(BigDecimal::of($second))
            ->toScale(4, RoundingMode::HalfUp);
    }
}