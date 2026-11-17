<?php

namespace App\Domains\Returns\Services;

use App\Domains\Customer\Models\Customer;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Returns\Enums\SalesReturnStatus;
use App\Domains\Returns\Models\SalesReturn;
use App\Domains\Returns\Models\SalesReturnItem;
use App\Domains\Sales\Enums\SalesOrderStatus;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Models\SalesOrderFulfillmentItem;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class SalesReturnService
{
    public function __construct(
        private readonly SalesReturnNumberGenerator $numberGenerator,
        private readonly InventoryService $inventoryService,
    ) {}

    
    
    public function createDraft(User $user, array $data): SalesReturn
    {
        return DB::transaction(function () use ($user, $data) {
            $companyId = $user->company_id;

            $salesOrder = SalesOrder::query()
                ->with('customer')
                ->lockForUpdate()
                ->findOrFail($data['sales_order_id']);

            $warehouse = Warehouse::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($data['warehouse_id']);

            if (! in_array($salesOrder->status, [
                SalesOrderStatus::CONFIRMED,
                SalesOrderStatus::PARTIALLY_FULFILLED,
                SalesOrderStatus::FULFILLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'sales_order_id' => 'Only confirmed or fulfilled sales orders can be returned.',
                ]);
            }

            if (empty($data['items']) || ! is_array($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'At least one return item is required.',
                ]);
            }

            $salesReturn = SalesReturn::query()->create([
                'company_id' => $companyId,
                'customer_id' => $salesOrder->customer_id,
                'sales_order_id' => $salesOrder->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'return_number' => $this->numberGenerator->next($companyId),
                'status' => SalesReturnStatus::DRAFT,
                'return_date' => $data['return_date'],
                'subtotal' => '0.0000',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = BigDecimal::zero();
            $pendingQuantities = [];

            foreach ($data['items'] as $index => $item) {
                $fulfillmentItem = SalesOrderFulfillmentItem::query()
                    ->with([
                        'fulfillment.salesOrder',
                        'salesOrderItem.product',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($item['sales_order_fulfillment_item_id']);

                $fulfillment = $fulfillmentItem->fulfillment;
                $orderItem = $fulfillmentItem->salesOrderItem;

                if (! $fulfillment || ! $orderItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.sales_order_fulfillment_item_id"
                            => 'The selected fulfillment item is invalid.',
                    ]);
                }

                if (
                    $fulfillment->sales_order_id !== $salesOrder->id ||
                    $orderItem->sales_order_id !== $salesOrder->id ||
                    $fulfillmentItem->sales_order_id !== $salesOrder->id
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.sales_order_fulfillment_item_id"
                            => 'The fulfillment item does not belong to the selected sales order.',
                    ]);
                }

                if ($fulfillment->dispatched_at === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.sales_order_fulfillment_item_id"
                            => 'Only dispatched fulfillment items can be returned.',
                    ]);
                }

                $quantity = BigDecimal::of((string) $item['quantity']);

                if (! $quantity->isGreaterThan(BigDecimal::zero())) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity"
                            => 'Quantity must be greater than zero.',
                    ]);
                }

                $alreadyReturned = BigDecimal::of(
                    (string) SalesReturnItem::query()
                        ->where(
                            'sales_order_fulfillment_item_id',
                            $fulfillmentItem->id
                        )
                        ->whereHas('salesReturn', function (Builder $query) {
                            $query->where(
                                'status',
                                '!=',
                                SalesReturnStatus::CANCELLED->value
                            );
                        })
                        ->sum('quantity')
                );

                $pendingQuantity = $pendingQuantities[$fulfillmentItem->id]
                    ?? BigDecimal::zero();

                $availableQuantity = BigDecimal::of(
                    (string) $fulfillmentItem->quantity_dispatched
                )
                    ->minus($alreadyReturned)
                    ->minus($pendingQuantity);

                if ($availableQuantity->isLessThan(BigDecimal::zero())) {
                    $availableQuantity = BigDecimal::zero();
                }

                if ($quantity->isGreaterThan($availableQuantity)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity"
                            => "Return quantity exceeds the remaining fulfilled quantity ({$availableQuantity}).",
                    ]);
                }

                $pendingQuantities[$fulfillmentItem->id] =
                    $pendingQuantity->plus($quantity);

                $unitPrice = BigDecimal::of(
                    (string) $orderItem->unit_price
                )->toScale(4, RoundingMode::HalfUp);

                $lineTotal = $quantity
                    ->multipliedBy($unitPrice)
                    ->toScale(4, RoundingMode::HalfUp);

                $salesReturn->items()->create([
                    'company_id' => $companyId,
                    'sales_order_id' => $salesOrder->id,
                    'sales_order_item_id' => $orderItem->id,
                    'sales_order_fulfillment_id' => $fulfillment->id,
                    'sales_order_fulfillment_item_id' => $fulfillmentItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => (string) $quantity->toScale(
                        4,
                        RoundingMode::HalfUp
                    ),
                    'unit_price' => (string) $unitPrice,
                    'line_total' => (string) $lineTotal,
                ]);

                $subtotal = $subtotal->plus($lineTotal);
            }

            $salesReturn->update([
                'subtotal' => (string) $subtotal->toScale(
                    4,
                    RoundingMode::HalfUp
                ),
            ]);

            return $salesReturn->load([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
                'items.salesOrderItem',
                'items.fulfillment',
                'items.fulfillmentItem',
            ]);
        }, 3);
    }



    public function approve(User $user, int $salesReturnId): SalesReturn
    {
        return DB::transaction(function () use ($user, $salesReturnId) {
            $salesReturn = SalesReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($salesReturnId);

            if ($salesReturn->status !== SalesReturnStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft sales returns can be approved.',
                ]);
            }

            if ($salesReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'A sales return must contain at least one item.',
                ]);
            }

            $salesReturn->update([
                'status' => SalesReturnStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $salesReturn->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'approvedBy',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function receive(User $user, int $salesReturnId): SalesReturn
    {
        return DB::transaction(function () use ($user, $salesReturnId) {
            $salesReturn = SalesReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($salesReturnId);

            if ($salesReturn->status !== SalesReturnStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved sales returns can be received.',
                ]);
            }

            if ($salesReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'A sales return must contain at least one item.',
                ]);
            }

            foreach ($salesReturn->items as $item) {
                $this->inventoryService->stockIn(
                    user: $user,
                    productId: $item->product_id,
                    warehouseId: $salesReturn->warehouse_id,
                    quantity: $item->quantity,
                    reference: $salesReturn->return_number,
                    notes: 'Goods received from customer sales return.',
                );
            }

            $salesReturn->update([
                'status' => SalesReturnStatus::RECEIVED,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            return $salesReturn->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'approvedBy',
                'receivedBy',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function complete(int $salesReturnId): SalesReturn
    {
        return DB::transaction(function () use ($salesReturnId) {
            $salesReturn = SalesReturn::query()
                ->lockForUpdate()
                ->findOrFail($salesReturnId);

            if ($salesReturn->status !== SalesReturnStatus::RECEIVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only received sales returns can be completed.',
                ]);
            }

            $salesReturn->update([
                'status' => SalesReturnStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            return $salesReturn->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'approvedBy',
                'receivedBy',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function cancel(int $salesReturnId): SalesReturn
    {
        return DB::transaction(function () use ($salesReturnId) {
            $salesReturn = SalesReturn::query()
                ->lockForUpdate()
                ->findOrFail($salesReturnId);

            if (! in_array($salesReturn->status, [
                SalesReturnStatus::DRAFT,
                SalesReturnStatus::APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or approved sales returns can be cancelled.',
                ]);
            }

            $salesReturn->update([
                'status' => SalesReturnStatus::CANCELLED,
            ]);

            return $salesReturn->fresh([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function getSalesReturns(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return SalesReturn::query()
            ->with([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'approvedBy',
                'receivedBy',
                'items.product',
            ])
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['customer_id'] ?? null,
                fn ($query, $customerId) => $query->where('customer_id', $customerId)
            )
            ->when(
                $filters['sales_order_id'] ?? null,
                fn ($query, $salesOrderId) => $query->where('sales_order_id', $salesOrderId)
            )
            ->when(
                $filters['warehouse_id'] ?? null,
                fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId)
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('return_number', 'ILIKE', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('name', 'ILIKE', "%{$search}%");
                            })
                            ->orWhereHas('salesOrder', function ($orderQuery) use ($search) {
                                $orderQuery->where('order_number', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(int $salesReturnId): SalesReturn
    {
        return SalesReturn::query()
            ->with([
                'customer',
                'salesOrder',
                'warehouse',
                'user',
                'approvedBy',
                'receivedBy',
                'items.product',
                'items.salesOrderItem',
                'items.fulfillment',
                'items.fulfillmentItem',
            ])
            ->findOrFail($salesReturnId);
    }
}