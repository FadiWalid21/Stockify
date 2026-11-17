<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Enums\SalesOrderStatus;
use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesInvoiceItem;
use App\Domains\Sales\Models\SalesInvoiceSequence;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesPayment;
use App\Domains\User\Models\User;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceService
{
    public function __construct(
        private SalesInvoiceNumberGenerator $numberGenerator,
    ) {}

    public function createDraft(
        User $user,
        int $salesOrderId,
        array $data,
    ): SalesInvoice {
        return DB::transaction(function () use ($user, $salesOrderId, $data) {
            $order = SalesOrder::query()
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if (! in_array($order->status, [
                SalesOrderStatus::CONFIRMED,
                SalesOrderStatus::PARTIALLY_FULFILLED,
                SalesOrderStatus::FULFILLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'Only confirmed or fulfilled sales orders can be invoiced.',
                ]);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'An invoice must contain at least one item.',
                ]);
            }

            $orderItems = $order->items()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $invoiceItemIds = array_column(
                $data['items'],
                'sales_order_item_id'
            );

            if (count($invoiceItemIds) !== count(array_unique($invoiceItemIds))) {
                throw ValidationException::withMessages([
                    'items' => 'A sales order item can only appear once per invoice.',
                ]);
            }

            $existingQuantities = DB::table('sales_invoice_items')
                ->join(
                    'sales_invoices',
                    'sales_invoices.id',
                    '=',
                    'sales_invoice_items.sales_invoice_id'
                )
                ->where('sales_invoice_items.company_id', $user->company_id)
                ->where('sales_invoice_items.sales_order_id', $order->id)
                ->where('sales_invoices.status', '!=', SalesInvoice::STATUS_CANCELLED)
                ->selectRaw(
                    'sales_invoice_items.sales_order_item_id, SUM(sales_invoice_items.quantity) as invoiced_quantity'
                )
                ->groupBy('sales_invoice_items.sales_order_item_id')
                ->pluck('invoiced_quantity', 'sales_order_item_id');

            $subtotal = BigDecimal::zero();
            $discountTotal = BigDecimal::zero();
            $taxTotal = BigDecimal::zero();
            $preparedItems = [];

            foreach ($data['items'] as $item) {
                $orderItem = $orderItems->get(
                    $item['sales_order_item_id']
                );

                if (! $orderItem) {
                    throw ValidationException::withMessages([
                        'items' => 'An invoice item does not belong to this sales order.',
                    ]);
                }

                $quantity = BigDecimal::of((string) $item['quantity']);
                $orderedQuantity = BigDecimal::of(
                    (string) $orderItem->quantity
                );

                $alreadyInvoiced = BigDecimal::of(
                    (string) ($existingQuantities[$orderItem->id] ?? '0')
                );

                $remainingQuantity = $orderedQuantity->minus(
                    $alreadyInvoiced
                );

                if ($quantity->isLessThanOrEqualTo(0)) {
                    throw ValidationException::withMessages([
                        'items' => 'Invoice quantities must be greater than zero.',
                    ]);
                }

                if ($quantity->isGreaterThan($remainingQuantity)) {
                    throw ValidationException::withMessages([
                        'items' => "The invoiced quantity exceeds the remaining quantity for product ID {$orderItem->product_id}.",
                    ]);
                }

                $unitPrice = BigDecimal::of(
                    (string) $orderItem->unit_price
                );

                $discountRate = BigDecimal::of(
                    (string) ($item['discount_rate'] ?? '0')
                );

                $taxRate = BigDecimal::of(
                    (string) ($item['tax_rate'] ?? '0')
                );

                if (
                    $discountRate->isLessThan(0)
                    || $discountRate->isGreaterThan(100)
                    || $taxRate->isLessThan(0)
                    || $taxRate->isGreaterThan(100)
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Discount and tax rates must be between 0 and 100.',
                    ]);
                }

                $lineSubtotal = $quantity
                    ->multipliedBy($unitPrice)
                    ->toScale(4, RoundingMode::HalfUp);

                $discountAmount = $lineSubtotal
                    ->multipliedBy($discountRate)
                    ->dividedBy('100', 4, RoundingMode::HalfUp);

                $taxableAmount = $lineSubtotal->minus($discountAmount);

                $taxAmount = $taxableAmount
                    ->multipliedBy($taxRate)
                    ->dividedBy('100', 4, RoundingMode::HalfUp);

                $lineTotal = $taxableAmount
                    ->plus($taxAmount)
                    ->toScale(4, RoundingMode::HalfUp);

                $subtotal = $subtotal->plus($lineSubtotal);
                $discountTotal = $discountTotal->plus($discountAmount);
                $taxTotal = $taxTotal->plus($taxAmount);

                $preparedItems[] = [
                    'sales_order_id' => $order->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => (string) $quantity->toScale(4, RoundingMode::HalfUp),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HalfUp),
                    'discount_rate' => (string) $discountRate->toScale(4, RoundingMode::HalfUp),
                    'discount_amount' => (string) $discountAmount,
                    'tax_rate' => (string) $taxRate->toScale(4, RoundingMode::HalfUp),
                    'tax_amount' => (string) $taxAmount,
                    'line_subtotal' => (string) $lineSubtotal,
                    'line_total' => (string) $lineTotal,
                ];
            }

            $subtotal = $subtotal->toScale(4, RoundingMode::HalfUp);
            $discountTotal = $discountTotal->toScale(4, RoundingMode::HalfUp);
            $taxTotal = $taxTotal->toScale(4, RoundingMode::HalfUp);

            $grandTotal = $subtotal
                ->minus($discountTotal)
                ->plus($taxTotal)
                ->toScale(4, RoundingMode::HalfUp);

            $invoice = SalesInvoice::query()->create([
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'user_id' => $user->id,
                'invoice_number' => $this->numberGenerator->generate(
                    $user->company_id
                ),
                'status' => SalesInvoice::STATUS_DRAFT,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => (string) $subtotal,
                'discount_total' => (string) $discountTotal,
                'tax_total' => (string) $taxTotal,
                'grand_total' => (string) $grandTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($preparedItems as $preparedItem) {
                $invoice->items()->create($preparedItem);
            }

            return $invoice->load([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
                'items.salesOrderItem',
            ]);
        }, 3);
    }

    public function issue(int $invoiceId): SalesInvoice
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = SalesInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            if ($invoice->status !== SalesInvoice::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only draft invoices can be issued.',
                ]);
            }

            if (! $invoice->items()->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'An invoice must contain at least one item.',
                ]);
            }

            $invoice->update([
                'status' => SalesInvoice::STATUS_ISSUED,
                'issued_at' => now(),
            ]);

            return $invoice->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
                'items.salesOrderItem',
            ]);
        }, 3);
    }

    public function cancelDraft(
        User $user,
        int $invoiceId,
    ): SalesInvoice {
        return DB::transaction(function () use ($user, $invoiceId) {
            $invoice = SalesInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            if ($invoice->status !== SalesInvoice::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only draft invoices can be cancelled.',
                ]);
            }

            $invoice->update([
                'status' => SalesInvoice::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $invoice->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
            ]);
        }, 3);
    }
    
    public function getInvoices(array $filters = [])
    {
        $query = SalesInvoice::query()
            ->with([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
            ])
            ->withSum([
                'paymentAllocations as amount_paid' => function ($query) {
                    $query->whereHas('payment', function ($paymentQuery) {
                        $paymentQuery->where(
                            'status',
                            SalesPayment::STATUS_COMPLETED
                        );
                    });
                },
            ], 'amount');

        $query = QueryFilter::apply(
            $query,
            $filters,
            ['invoice_number'],
            ['status', 'customer_id', 'sales_order_id'],
            [
                'invoice_number',
                'invoice_date',
                'due_date',
                'created_at',
                'grand_total',
            ],
        );

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }

    
    public function show(int $invoiceId): SalesInvoice
    {
        return SalesInvoice::query()
            ->with([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
                'items.salesOrderItem',
                'paymentAllocations.payment',
            ])
            ->withSum([
                'paymentAllocations as amount_paid' => function ($query) {
                    $query->whereHas('payment', function ($paymentQuery) {
                        $paymentQuery->where(
                            'status',
                            SalesPayment::STATUS_COMPLETED
                        );
                    });
                },
            ], 'amount')
            ->findOrFail($invoiceId);
    }
}