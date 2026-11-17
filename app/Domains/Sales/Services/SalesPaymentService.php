<?php

namespace App\Domains\Sales\Services;

use App\Domains\Sales\Models\SalesInvoice;
use App\Domains\Sales\Models\SalesPayment;
use App\Domains\User\Models\User;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesPaymentService
{
    public function __construct(
        private SalesPaymentNumberGenerator $numberGenerator,
    ) {}

    
    public function create(
        User $user,
        array $data,
    ): SalesPayment {
        return DB::transaction(function () use ($user, $data) {
            $paymentAmount = BigDecimal::of((string) $data['amount'])
                ->toScale(4, RoundingMode::HalfUp);

            if ($paymentAmount->isLessThanOrEqualTo(0)) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (empty($data['allocations'])) {
                throw ValidationException::withMessages([
                    'allocations' => 'At least one invoice allocation is required.',
                ]);
            }

            $invoiceIds = array_column(
                $data['allocations'],
                'sales_invoice_id'
            );

            if (count($invoiceIds) !== count(array_unique($invoiceIds))) {
                throw ValidationException::withMessages([
                    'allocations' => 'An invoice can only appear once per payment.',
                ]);
            }

            sort($invoiceIds);

            $invoices = SalesInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($invoices->count() !== count($invoiceIds)) {
                throw ValidationException::withMessages([
                    'allocations' => 'One or more invoices could not be found.',
                ]);
            }

            $customerId = $invoices->first()->customer_id;

            $totalAllocated = BigDecimal::zero();
            $preparedAllocations = [];

            foreach ($data['allocations'] as $allocation) {
                $invoice = $invoices->get(
                    $allocation['sales_invoice_id']
                );

                if ($invoice->customer_id !== $customerId) {
                    throw ValidationException::withMessages([
                        'allocations' => 'All invoices must belong to the same customer.',
                    ]);
                }

                if (! in_array($invoice->status, [
                    SalesInvoice::STATUS_ISSUED,
                    SalesInvoice::STATUS_PARTIALLY_PAID,
                    SalesInvoice::STATUS_OVERDUE,
                ], true)) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Only issued invoices with outstanding balances can receive payments.',
                    ]);
                }

                $allocationAmount = BigDecimal::of(
                    (string) $allocation['amount']
                )->toScale(4, RoundingMode::HalfUp);

                if ($allocationAmount->isLessThanOrEqualTo(0)) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Allocation amounts must be greater than zero.',
                    ]);
                }

                $amountPaid = BigDecimal::of(
                    (string) $invoice->amount_paid
                );

                $outstanding = BigDecimal::of(
                    (string) $invoice->grand_total
                )->minus($amountPaid);

                if ($allocationAmount->isGreaterThan($outstanding)) {
                    throw ValidationException::withMessages([
                        'allocations' => "Allocation exceeds the outstanding balance of invoice {$invoice->invoice_number}.",
                    ]);
                }

                $totalAllocated = $totalAllocated->plus($allocationAmount);

                $preparedAllocations[] = [
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $customerId,
                    'amount' => (string) $allocationAmount,
                ];
            }

            if ($totalAllocated->isGreaterThan($paymentAmount)) {
                throw ValidationException::withMessages([
                    'allocations' => 'Total allocations cannot exceed the payment amount.',
                ]);
            }

            $payment = SalesPayment::query()->create([
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'payment_number' => $this->numberGenerator->generate(
                    $user->company_id
                ),
                'payment_method' => $data['payment_method'],
                'status' => SalesPayment::STATUS_COMPLETED,
                'payment_date' => $data['payment_date'],
                'amount' => (string) $paymentAmount,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($preparedAllocations as $allocation) {
                $payment->allocations()->create($allocation);
            }

            foreach ($invoices as $invoice) {
                $invoice->refresh();

                $amountPaid = BigDecimal::of(
                    (string) $invoice->amount_paid
                );

                $grandTotal = BigDecimal::of(
                    (string) $invoice->grand_total
                );

                if ($amountPaid->isGreaterThanOrEqualTo($grandTotal)) {
                    $newStatus = SalesInvoice::STATUS_PAID;
                } elseif (
                    $invoice->due_date !== null &&
                    $invoice->due_date->lt(today())
                ) {
                    $newStatus = SalesInvoice::STATUS_OVERDUE;
                } else {
                    $newStatus = SalesInvoice::STATUS_PARTIALLY_PAID;
                }

                $invoice->update([
                    'status' => $newStatus,
                ]);
            }

            return $payment->load([
                'customer',
                'user',
                'allocations.invoice',
            ]);
        }, 3);
    }

    
    
    public function allocate(
        int $paymentId,
        int $invoiceId,
        string $amount,
    ): SalesPayment {
        return DB::transaction(function () use (
            $paymentId,
            $invoiceId,
            $amount
        ) {
            $payment = SalesPayment::query()
                ->lockForUpdate()
                ->findOrFail($paymentId);

            if ($payment->status !== SalesPayment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'payment' => 'Only completed payments can be allocated.',
                ]);
            }

            $invoice = SalesInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            if ($invoice->customer_id !== $payment->customer_id) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The invoice must belong to the payment customer.',
                ]);
            }

            if (! in_array($invoice->status, [
                SalesInvoice::STATUS_ISSUED,
                SalesInvoice::STATUS_PARTIALLY_PAID,
                SalesInvoice::STATUS_OVERDUE,
            ], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice cannot receive payments.',
                ]);
            }

            $allocationAmount = BigDecimal::of($amount)
                ->toScale(4, RoundingMode::HalfUp);

            if ($allocationAmount->isLessThanOrEqualTo(0)) {
                throw ValidationException::withMessages([
                    'amount' => 'Allocation amount must be greater than zero.',
                ]);
            }

            $allocatedTotal = BigDecimal::of(
                (string) $payment->allocations()->sum('amount')
            );

            $paymentBalance = BigDecimal::of(
                (string) $payment->amount
            )->minus($allocatedTotal);

            if ($allocationAmount->isGreaterThan($paymentBalance)) {
                throw ValidationException::withMessages([
                    'amount' => 'Allocation exceeds the unallocated payment balance.',
                ]);
            }

            $outstanding = BigDecimal::of(
                (string) $invoice->outstanding_amount
            );

            if ($allocationAmount->isGreaterThan($outstanding)) {
                throw ValidationException::withMessages([
                    'amount' => 'Allocation exceeds the invoice outstanding balance.',
                ]);
            }

            $existingAllocation = $payment->allocations()
                ->where('sales_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if ($existingAllocation) {
                $newAmount = BigDecimal::of(
                    (string) $existingAllocation->amount
                )->plus($allocationAmount);

                $existingAllocation->update([
                    'amount' => (string) $newAmount,
                ]);
            } else {
                $payment->allocations()->create([
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $payment->customer_id,
                    'amount' => (string) $allocationAmount,
                ]);
            }

            $invoice->refresh();

            $amountPaid = BigDecimal::of(
                (string) $invoice->amount_paid
            );

            $grandTotal = BigDecimal::of(
                (string) $invoice->grand_total
            );

            if ($amountPaid->isGreaterThanOrEqualTo($grandTotal)) {
                $newStatus = SalesInvoice::STATUS_PAID;
            } elseif (
                $invoice->due_date !== null &&
                $invoice->due_date->lt(today())
            ) {
                $newStatus = SalesInvoice::STATUS_OVERDUE;
            } else {
                $newStatus = SalesInvoice::STATUS_PARTIALLY_PAID;
            }

            $invoice->update([
                'status' => $newStatus,
            ]);

            return $payment->load([
                'customer',
                'user',
                'allocations.invoice',
            ]);
        }, 3);
    }



    public function cancel(int $paymentId): SalesPayment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = SalesPayment::query()
                ->lockForUpdate()
                ->findOrFail($paymentId);

            if ($payment->status !== SalesPayment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'payment' => 'Only completed payments can be cancelled.',
                ]);
            }

            $allocations = $payment->allocations()
                ->orderBy('sales_invoice_id')
                ->get();

            $invoiceIds = $allocations
                ->pluck('sales_invoice_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $invoices = SalesInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($invoices->count() !== count($invoiceIds)) {
                throw ValidationException::withMessages([
                    'payment' => 'One or more allocated invoices could not be found.',
                ]);
            }

            // Cancel the payment so its allocations no longer count
            // toward invoice paid balances.
            $payment->update([
                'status' => SalesPayment::STATUS_CANCELLED,
            ]);

            foreach ($invoices as $invoice) {
                $invoice->refresh();

                $amountPaid = BigDecimal::of(
                    (string) $invoice->amount_paid
                );

                if ($amountPaid->isGreaterThanOrEqualTo(
                    BigDecimal::of((string) $invoice->grand_total)
                )) {
                    $newStatus = SalesInvoice::STATUS_PAID;
                } elseif ($amountPaid->isGreaterThan(0)) {
                    $newStatus = SalesInvoice::STATUS_PARTIALLY_PAID;
                } elseif (
                    $invoice->due_date !== null &&
                    $invoice->due_date->lt(today())
                ) {
                    $newStatus = SalesInvoice::STATUS_OVERDUE;
                } else {
                    $newStatus = SalesInvoice::STATUS_ISSUED;
                }

                $invoice->update([
                    'status' => $newStatus,
                ]);
            }

            return $payment->load([
                'customer',
                'user',
                'allocations.invoice',
            ]);
        }, 3);
    }


    public function getPayments(array $filters = [])
    {
        $query = SalesPayment::query()
            ->with([
                'customer',
                'user',
            ]);

        $query = QueryFilter::apply(
            $query,
            $filters,
            [
                'payment_number',
                'reference',
            ],
            [
                'payment_method',
                'status',
                'payment_date',
                'customer_id',
            ],
            [
                'payment_number',
                'payment_date',
                'amount',
                'created_at',
            ],
        );

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }


    public function show(int $paymentId): SalesPayment
    {
        return SalesPayment::query()
            ->with([
                'customer',
                'user',
                'allocations.invoice',
            ])
            ->findOrFail($paymentId);
    }
}
