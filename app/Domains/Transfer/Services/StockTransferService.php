<?php

namespace App\Domains\Transfer\Services;

use App\Domains\Transfer\Models\StockTransfer;
use App\Domains\Transfer\Models\StockTransferItem;
use App\Domains\Transfer\Enums\StockTransferStatus;
use App\Domains\Inventory\Enums\InventoryOperationType;
use App\Domains\Inventory\Enums\StockMovementType;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryOperation;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use App\Support\Query\Pagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    
    public function createDraft(
        User $user,
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        ?string $reference = null,
        ?string $notes = null,
    ): StockTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Source and destination warehouses must be different.',
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'A transfer must contain at least one product.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $fromWarehouseId,
            $toWarehouseId,
            $items,
            $reference,
            $notes,
        ) {
            $warehouseIds = [
                $fromWarehouseId,
                $toWarehouseId,
            ];

            sort($warehouseIds);

            $warehouses = Warehouse::query()
                ->whereIn('id', $warehouseIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($warehouses->count() !== 2) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'One or both warehouses do not exist.',
                ]);
            }

            $fromWarehouse = $warehouses->get($fromWarehouseId);
            $toWarehouse = $warehouses->get($toWarehouseId);

            if (! $fromWarehouse->is_active || ! $toWarehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Both warehouses must be active.',
                ]);
            }

            $productIds = array_column($items, 'product_id');

            if (count($productIds) !== count(array_unique($productIds))) {
                throw ValidationException::withMessages([
                    'items' => 'A product can only appear once in a transfer.',
                ]);
            }

            foreach ($items as $item) {
                if (! isset($item['product_id'], $item['quantity'])
                    || ! is_numeric($item['quantity'])
                    || (float) $item['quantity'] <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Each item must have a valid product and positive quantity.',
                    ]);
                }
            }

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products do not exist.',
                ]);
            }

            foreach ($products as $product) {
                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => 'All products must be active.',
                    ]);
                }
            }

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'created_by' => $user->id,
                'status' => StockTransferStatus::DRAFT,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer->load([
                'items.product',
                'fromWarehouse',
                'toWarehouse',
            ]);
        }, attempts: 3);
    }


    public function approve(User $user, int $transferId): StockTransfer
        {
            return DB::transaction(function () use ($user, $transferId) {
                $transfer = StockTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($transferId);

                if ($transfer->status !== StockTransferStatus::DRAFT) {
                    throw ValidationException::withMessages([
                        'status' => 'Only draft transfers can be approved.',
                    ]);
                }

                $transfer->update([
                    'status' => StockTransferStatus::APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                return $transfer->load([
                    'items.product',
                    'fromWarehouse',
                    'toWarehouse',
                    'creator',
                    'approver',
                    'canceller'
                ]);
            }, attempts: 3);
        }



    public function dispatch(User $user, int $transferId): StockTransfer
    {
        return DB::transaction(function () use ($user, $transferId) {
            $transfer = StockTransfer::query()
                ->lockForUpdate()
                ->with('items')
                ->findOrFail($transferId);

            if ($transfer->status !== StockTransferStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved transfers can be dispatched.',
                ]);
            }

            $items = $transfer->items->sortBy('product_id');
            $productIds = $items->pluck('product_id')->all();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are no longer available.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($transfer->from_warehouse_id);

            $allowNegativeStock = $user->company()
                ->with('setting')
                ->firstOrFail()
                ->setting?->allow_negative_stock ?? false;

            $balances = InventoryBalance::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items as $item) {
                $available = (float) ($balances->get($item->product_id)?->quantity ?? 0);

                if (! $allowNegativeStock && $available < (float) $item->quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for product ID {$item->product_id}. Available: {$available}.",
                    ]);
                }
            }

            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::STOCK_OUT,
                'reference' => $transfer->reference ?? "TRANSFER-{$transfer->id}",
                'notes' => "Stock dispatched for transfer #{$transfer->id}.",
            ]);

            foreach ($items as $item) {
                $balance = $balances->get($item->product_id);

                if (! $balance) {
                    $balance = InventoryBalance::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => 0,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse->id,
                    'inventory_operation_id' => $operation->id,
                    'quantity' => $item->quantity,
                    'type' => StockMovementType::OUT,
                    'notes' => "Dispatched for transfer #{$transfer->id}.",
                ]);

                $balance->decrement('quantity', $item->quantity);
            }

            $transfer->update([
                'status' => StockTransferStatus::IN_TRANSIT,
            ]);

            return $transfer->load([
                'items.product',
                'fromWarehouse',
                'toWarehouse',
                'creator',
                'approver',
                'canceller'
            ]);
        }, attempts: 3);
    }


    public function receive(User $user, int $transferId): StockTransfer
    {
        return DB::transaction(function () use ($user, $transferId) {
            $transfer = StockTransfer::query()
                ->lockForUpdate()
                ->with('items')
                ->findOrFail($transferId);

            if ($transfer->status !== StockTransferStatus::IN_TRANSIT) {
                throw ValidationException::withMessages([
                    'status' => 'Only in-transit transfers can be received.',
                ]);
            }

            $items = $transfer->items->sortBy('product_id');
            $productIds = $items->pluck('product_id')->all();

            Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($transfer->to_warehouse_id);

            $balances = InventoryBalance::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::STOCK_IN,
                'reference' => $transfer->reference ?? "TRANSFER-{$transfer->id}",
                'notes' => "Stock received for transfer #{$transfer->id}.",
            ]);

            foreach ($items as $item) {
                $balance = $balances->get($item->product_id);

                if (! $balance) {
                    $balance = InventoryBalance::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouse->id,
                        'quantity' => 0,
                    ]);
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse->id,
                    'inventory_operation_id' => $operation->id,
                    'quantity' => $item->quantity,
                    'type' => StockMovementType::IN,
                    'notes' => "Received from transfer #{$transfer->id}.",
                ]);

                $balance->increment('quantity', $item->quantity);
            }

            $transfer->update([
                'status' => StockTransferStatus::RECEIVED,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            return $transfer->load([
                'items.product',
                'fromWarehouse',
                'toWarehouse',
                'creator',
                'approver',
                'receiver',
                'canceller',
            ]);
        }, attempts: 3);
    }

    public function getTransfers(array $params = [])
    {
        $query = StockTransfer::query()->with([
            'fromWarehouse',
            'toWarehouse',
            'items.product',
            'creator',
            'approver',
            'receiver',
        ]);

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $params['from_warehouse_id']);
        }

        if (!empty($params['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $params['to_warehouse_id']);
        }

        if (!empty($params['search'])) {
            $search = $params['search'];

            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ILIKE', "%{$search}%")
                    ->orWhere('notes', 'ILIKE', "%{$search}%");
            });
        }

        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }

    public function show(int $transferId): StockTransfer
    {
        return StockTransfer::query()
            ->with([
                'fromWarehouse',
                'toWarehouse',
                'items.product',
                'creator',
                'approver',
                'receiver',
                'canceller',
            ])
            ->findOrFail($transferId);
    }

    public function cancel(User $user, int $transferId): StockTransfer
    {
        return DB::transaction(function () use ($user, $transferId) {
            $transfer = StockTransfer::query()
                ->lockForUpdate()
                ->findOrFail($transferId);

            if (! in_array($transfer->status, [
                StockTransferStatus::DRAFT,
                StockTransferStatus::APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or approved transfers can be cancelled.',
                ]);
            }

            $transfer->update([
                'status' => StockTransferStatus::CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $transfer->load([
                'fromWarehouse',
                'toWarehouse',
                'items.product',
                'creator',
                'approver',
                'receiver',
                'canceller',
            ]);
        }, 3);
    }

}