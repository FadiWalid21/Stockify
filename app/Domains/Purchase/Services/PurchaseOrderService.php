<?php

namespace App\Domains\Purchase\Services;

use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Purchase\Models\PurchaseOrder;
use App\Domains\User\Models\User;
use App\Domains\Product\Models\Product;
use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use App\Domains\Supplier\Models\Supplier;
use App\Domains\Warehouse\Models\Warehouse;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        protected PurchaseOrderNumberGenerator $numberGenerator,
        protected InventoryService $inventoryService,
    ) {}

    public function createDraft(
        User $user,
        array $data,
    ): PurchaseOrder {
        return DB::transaction(function () use ($user, $data) {
            $supplier = Supplier::query()
                ->lockForUpdate()
                ->findOrFail($data['supplier_id']);

            if (! $supplier->is_active) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'The selected supplier is inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($data['warehouse_id']);

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The selected warehouse is inactive.',
                ]);
            }

            $items = collect($data['items']);

            if ($items->pluck('product_id')->unique()->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Each product can only appear once in a purchase order.',
                ]);
            }

            foreach ($items as $item) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Product {$product->name} is inactive.",
                    ]);
                }
            }

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'order_number' => $this->numberGenerator->generate($user->company_id),
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $purchaseOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            return $purchaseOrder->load([
                'supplier',
                'warehouse',
                'creator',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function approve(
        User $user,
        int $purchaseOrderId,
    ): PurchaseOrder {
        return DB::transaction(function () use ($user, $purchaseOrderId) {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if ($purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft purchase orders can be approved.',
                ]);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $purchaseOrder->load([
                'supplier',
                'warehouse',
                'creator',
                'approver',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function cancel(
        User $user,
        int $purchaseOrderId,
    ): PurchaseOrder {
        return DB::transaction(function () use ($user, $purchaseOrderId) {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if (! in_array($purchaseOrder->status, [
                PurchaseOrderStatus::DRAFT,
                PurchaseOrderStatus::APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or approved purchase orders can be cancelled.',
                ]);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $purchaseOrder->load([
                'supplier',
                'warehouse',
                'creator',
                'canceller',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function receive(
        User $user,
        int $purchaseOrderId,
        array $data,
    ): PurchaseOrder {
        return DB::transaction(function () use ($user, $purchaseOrderId, $data) {
            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if (! in_array($purchaseOrder->status, [
                PurchaseOrderStatus::APPROVED,
                PurchaseOrderStatus::PARTIALLY_RECEIVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved or partially received purchase orders can receive goods.',
                ]);
            }

            $receivedItems = collect($data['items']);

            if ($receivedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'At least one item must be received.',
                ]);
            }

            if (
                $receivedItems->pluck('purchase_order_item_id')->unique()->count()
                !== $receivedItems->count()
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Each purchase order item can only appear once per receipt.',
                ]);
            }

            $orderItems = $purchaseOrder->items->keyBy('id');

            // Validate every item before changing inventory.
            foreach ($receivedItems as $receivedItem) {
                $orderItem = $orderItems->get(
                    $receivedItem['purchase_order_item_id']
                );

                if (! $orderItem) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more items do not belong to this purchase order.',
                    ]);
                }

                $previouslyReceived = (float) $orderItem->receiptItems()
                    ->sum('quantity_received');

                $remainingQuantity = (float) $orderItem->quantity
                    - $previouslyReceived;

                $quantityToReceive = (float) $receivedItem['quantity_received'];

                if (
                    $quantityToReceive <= 0 ||
                    round($quantityToReceive, 4) > round($remainingQuantity, 4)
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Received quantity for product {$orderItem->product_id} must be greater than zero and cannot exceed the remaining quantity of {$remainingQuantity}.",
                    ]);
                }
            }

            $receipt = $purchaseOrder->receipts()->create([
                'user_id' => $user->id,
                'received_at' => now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create receipt lines and add received quantities to inventory.
            foreach ($receivedItems as $receivedItem) {
                $orderItem = $orderItems->get(
                    $receivedItem['purchase_order_item_id']
                );

                $quantity = $receivedItem['quantity_received'];

                $receipt->items()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_order_item_id' => $orderItem->id,
                    'quantity_received' => $quantity,
                ]);

                $this->inventoryService->stockIn(
                    $user,
                    $orderItem->product_id,
                    $purchaseOrder->warehouse_id,
                    $quantity,
                    $purchaseOrder->order_number . ' / Receipt ' . $receipt->id,
                    'Goods received against purchase order ' . $purchaseOrder->order_number,
                );
            }

            // Determine whether all ordered quantities have been received.
            $allItemsReceived = $purchaseOrder->items->every(function ($orderItem) {
                $receivedQuantity = (float) $orderItem->receiptItems()
                    ->sum('quantity_received');

                return round($receivedQuantity, 4)
                    >= round((float) $orderItem->quantity, 4);
            });

            $purchaseOrder->update([
                'status' => $allItemsReceived
                    ? PurchaseOrderStatus::RECEIVED
                    : PurchaseOrderStatus::PARTIALLY_RECEIVED,
            ]);

            return $purchaseOrder->fresh()->load([
                'supplier',
                'warehouse',
                'creator',
                'approver',
                'items.product',
                'receipts.user',
                'receipts.items.purchaseOrderItem.product',
            ]);
        }, attempts: 3);
    }

    public function updateDraft(
        int $purchaseOrderId,
        array $data,
    ): PurchaseOrder {
        return DB::transaction(function () use ($purchaseOrderId, $data) {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if ($purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft purchase orders can be updated.',
                ]);
            }

            // Validate supplier if supplied.
            if (array_key_exists('supplier_id', $data)) {
                $supplier = Supplier::query()
                    ->lockForUpdate()
                    ->findOrFail($data['supplier_id']);

                if (! $supplier->is_active) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'The selected supplier is inactive.',
                    ]);
                }
            }

            // Validate warehouse if supplied.
            if (array_key_exists('warehouse_id', $data)) {
                $warehouse = Warehouse::query()
                    ->lockForUpdate()
                    ->findOrFail($data['warehouse_id']);

                if (! $warehouse->is_active) {
                    throw ValidationException::withMessages([
                        'warehouse_id' => 'The selected warehouse is inactive.',
                    ]);
                }
            }

            // Replace order items only when items are included in the request.
            if (array_key_exists('items', $data)) {
                $items = collect($data['items']);

                if ($items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'A purchase order must contain at least one item.',
                    ]);
                }

                if (
                    $items->pluck('product_id')->unique()->count()
                    !== $items->count()
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Each product can only appear once in a purchase order.',
                    ]);
                }

                foreach ($items as $item) {
                    $product = Product::query()
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    if (! $product->is_active) {
                        throw ValidationException::withMessages([
                            'items' => "Product {$product->name} is inactive.",
                        ]);
                    }
                }

                $purchaseOrder->items()->delete();

                foreach ($items as $item) {
                    $purchaseOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                    ]);
                }
            }

            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'] ?? $purchaseOrder->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? $purchaseOrder->warehouse_id,
                'order_date' => $data['order_date'] ?? $purchaseOrder->order_date,
                'expected_at' => array_key_exists('expected_at', $data)
                    ? $data['expected_at']
                    : $purchaseOrder->expected_at,
                'notes' => array_key_exists('notes', $data)
                    ? $data['notes']
                    : $purchaseOrder->notes,
            ]);

            return $purchaseOrder->fresh()->load([
                'supplier',
                'warehouse',
                'creator',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function getPurchaseOrders(array $filters = [])
    {
        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'warehouse',
                'creator',
                'items.product',
            ]);

        QueryFilter::apply(
            $query,
            $filters,
            searchableFields: ['order_number', 'notes'],
            filterableFields: [
                'status',
                'supplier_id',
                'warehouse_id',
            ],
            sortableFields: [
                'order_number',
                'order_date',
                'expected_at',
                'created_at',
            ],
        );

        return $query
            ->latest()
            ->paginate(Pagination::perPage(
                isset($filters['per_page'])
                    ? (int) $filters['per_page']
                    : null
            ))
            ->withQueryString();
    }

    public function show(int $purchaseOrderId): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with([
                'supplier',
                'warehouse',
                'creator',
                'approver',
                'canceller',
                'items.product',
                'receipts.user',
                'receipts.items.purchaseOrderItem.product',
            ])
            ->findOrFail($purchaseOrderId);
    }
}