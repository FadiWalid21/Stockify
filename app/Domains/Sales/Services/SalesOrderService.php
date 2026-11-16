<?php

namespace App\Domains\Sales\Services;

use App\Domains\Customer\Models\Customer;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Sales\Enums\SalesOrderStatus;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use App\Domains\Product\Models\Product;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
{
    public function __construct(
    private SalesOrderNumberGenerator $numberGenerator,
    private InventoryService $inventoryService,
) {}

    public function createDraft(User $user, array $data): SalesOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $customer = Customer::query()
                ->whereKey($data['customer_id'])
                ->where('is_active', true)
                ->first();

            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is invalid or inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->whereKey($data['warehouse_id'])
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The selected warehouse is invalid or inactive.',
                ]);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'A sales order must contain at least one item.',
                ]);
            }

            $productIds = array_column($data['items'], 'product_id');

            if (count($productIds) !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => 'A product can only appear once in a sales order.',
                ]);
            }

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are invalid or inactive.',
                ]);
            }

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                if ($quantity <= 0 || $unitPrice < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Quantities must be positive and prices cannot be negative.',
                    ]);
                }

                $subtotal += round($quantity * $unitPrice, 4);
            }

            $order = SalesOrder::query()->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'order_number' => $this->numberGenerator->generate($user->company_id),
                'status' => SalesOrderStatus::DRAFT,
                'order_date' => $data['order_date'],
                'expected_at' => $data['expected_at'] ?? null,
                'subtotal' => round($subtotal, 4),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($quantity * $unitPrice, 4),
                ]);
            }

            return $order->load([
                'customer',
                'warehouse',
                'creator',
                'items.product',
            ]);
        }, 3);
    }

    public function place(User $user, int $salesOrderId): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId) {
            $order = SalesOrder::query()
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if ($order->status !== SalesOrderStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft sales orders can be placed.',
                ]);
            }

            $order->update([
                'status' => SalesOrderStatus::PLACED,
                'placed_at' => now(),
            ]);

            return $order->load([
                'customer',
                'warehouse',
                'creator',
                'items.product',
            ]);
        }, 3);
    }

    public function confirm(User $user, int $salesOrderId): SalesOrder
    {
        return DB::transaction(function () use ($user, $salesOrderId) {
            $order = SalesOrder::query()
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if ($order->status !== SalesOrderStatus::PLACED) {
                throw ValidationException::withMessages([
                    'status' => 'Only placed sales orders can be confirmed.',
                ]);
            }

            $order->update([
                'status' => SalesOrderStatus::CONFIRMED,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ]);

            return $order->load([
                'customer',
                'warehouse',
                'creator',
                'confirmer',
                'items.product',
            ]);
        }, 3);
    }

    public function cancel(User $user, int $salesOrderId): SalesOrder
    {
        return DB::transaction(function () use ($user, $salesOrderId) {
            $order = SalesOrder::query()
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if (! in_array($order->status, [
                SalesOrderStatus::DRAFT,
                SalesOrderStatus::PLACED,
                SalesOrderStatus::CONFIRMED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This sales order cannot be cancelled.',
                ]);
            }

            $order->update([
                'status' => SalesOrderStatus::CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $order->load([
                'customer',
                'warehouse',
                'creator',
                'canceller',
                'items.product',
            ]);
        }, 3);
    }

    public function updateDraft(
        User $user,
        int $salesOrderId,
        array $data,
    ): SalesOrder {
        return DB::transaction(function () use ($salesOrderId, $data) {
            $order = SalesOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if ($order->status !== SalesOrderStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft sales orders can be updated.',
                ]);
            }

            $customerId = $data['customer_id'] ?? $order->customer_id;
            $warehouseId = $data['warehouse_id'] ?? $order->warehouse_id;

            $customer = Customer::query()
                ->whereKey($customerId)
                ->where('is_active', true)
                ->first();

            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is invalid or inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->whereKey($warehouseId)
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The selected warehouse is invalid or inactive.',
                ]);
            }

            $items = $data['items'] ?? null;
            $subtotal = (float) $order->subtotal;

            if ($items !== null) {
                if (empty($items)) {
                    throw ValidationException::withMessages([
                        'items' => 'A sales order must contain at least one item.',
                    ]);
                }

                $productIds = array_column($items, 'product_id');

                if (count($productIds) !== count(array_unique($productIds))) {
                    throw ValidationException::withMessages([
                        'items' => 'A product can only appear once in a sales order.',
                    ]);
                }

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->get();

                if ($products->count() !== count($productIds)) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more selected products are invalid or inactive.',
                    ]);
                }

                $subtotal = 0;

                foreach ($items as $item) {
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];

                    if ($quantity <= 0 || $unitPrice < 0) {
                        throw ValidationException::withMessages([
                            'items' => 'Quantities must be positive and prices cannot be negative.',
                        ]);
                    }

                    $subtotal += round($quantity * $unitPrice, 4);
                }

                $order->items()->delete();

                foreach ($items as $item) {
                    $quantity = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => round($quantity * $unitPrice, 4),
                    ]);
                }
            }

            $order->update([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_at' => array_key_exists('expected_at', $data)
                    ? $data['expected_at']
                    : $order->expected_at,
                'subtotal' => round($subtotal, 4),
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            return $order->fresh()->load([
                'customer',
                'warehouse',
                'creator',
                'items.product',
            ]);
        }, 3);
    }

    public function dispatch(
        User $user,
        int $salesOrderId,
        array $data,
    ): SalesOrder {
        return DB::transaction(function () use ($user, $salesOrderId, $data) {
            $order = SalesOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if (! in_array($order->status, [
                SalesOrderStatus::CONFIRMED,
                SalesOrderStatus::PARTIALLY_FULFILLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only confirmed or partially fulfilled orders can be dispatched.',
                ]);
            }

            if (empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'A fulfillment must contain at least one item.',
                ]);
            }

            $itemIds = array_column($data['items'], 'sales_order_item_id');

            if (count($itemIds) !== count(array_unique($itemIds))) {
                throw ValidationException::withMessages([
                    'items' => 'An order item can only appear once per fulfillment.',
                ]);
            }

            $orderItems = $order->items->keyBy('id');

            $fulfillment = $order->fulfillments()->create([
                'user_id' => $user->id,
                'dispatched_at' => now(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $orderItem = $orderItems->get($item['sales_order_item_id']);

                if (! $orderItem) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more items do not belong to this sales order.',
                    ]);
                }

                $quantity = (float) $item['quantity_dispatched'];

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Dispatched quantities must be greater than zero.',
                    ]);
                }

                $alreadyDispatched = (float) $orderItem->fulfillmentItems()
                    ->sum('quantity_dispatched');

                $remainingQuantity = (float) $orderItem->quantity - $alreadyDispatched;

                if ($quantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        'items' => "The dispatched quantity exceeds the remaining quantity for product ID {$orderItem->product_id}.",
                    ]);
                }

                $fulfillment->items()->create([
                    'sales_order_id' => $order->id,
                    'sales_order_item_id' => $orderItem->id,
                    'quantity_dispatched' => $quantity,
                ]);

                $this->inventoryService->stockOut(
                    $user,
                    $orderItem->product_id,
                    $order->warehouse_id,
                    $quantity,
                    $data['reference'] ?? null,
                    $data['notes'] ?? null,
                );
            }

            $allItemsFulfilled = $order->items->every(function ($orderItem) {
                $dispatched = (float) $orderItem->fulfillmentItems()
                    ->sum('quantity_dispatched');

                return $dispatched >= (float) $orderItem->quantity;
            });

            $order->update([
                'status' => $allItemsFulfilled
                    ? SalesOrderStatus::FULFILLED
                    : SalesOrderStatus::PARTIALLY_FULFILLED,
            ]);

            return $order->fresh()->load([
                'customer',
                'warehouse',
                'creator',
                'confirmer',
                'items.product',
                'fulfillments.user',
                'fulfillments.items.salesOrderItem.product',
            ]);
        }, 3);
    }

    public function getSalesOrders(array $params = [])
    {
        $query = SalesOrder::query()
            ->with([
                'customer',
                'warehouse',
                'creator',
                'items.product',
            ]);

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['customer_id'])) {
            $query->where('customer_id', $params['customer_id']);
        }

        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', $params['warehouse_id']);
        }

        QueryFilter::apply(
            $query,
            $params,
            ['order_number', 'notes'],
            [],
            ['order_number', 'order_date', 'subtotal', 'created_at'],
        );

        return $query
            ->latest('created_at')
            ->paginate(Pagination::perPage(
                isset($params['per_page']) ? (int) $params['per_page'] : null
            ));
    }

    public function show(int $salesOrderId): SalesOrder
    {
        return SalesOrder::query()
            ->with([
                'customer',
                'warehouse',
                'creator',
                'confirmer',
                'canceller',
                'items.product',
                'fulfillments.user',
                'fulfillments.items.salesOrderItem.product',
            ])
            ->findOrFail($salesOrderId);
    }
}