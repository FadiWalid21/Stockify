<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Enums\InventoryOperationType;
use App\Domains\Inventory\Enums\StockMovementType;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryOperation;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;

class InventoryService
{
    public function stockIn(
        User $user,
        int $productId,
        int $warehouseId,
        string|int|float $quantity,
        ?string $reference = null,
        ?string $notes = null,
    ): InventoryOperation {
        if (! is_numeric($quantity) || (float) $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $productId,
            $warehouseId,
            $quantity,
            $reference,
            $notes,
        ) {

            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($warehouseId);

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'This warehouse is inactive.',
                ]);
            }

            // Create the parent inventory operation.
            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::STOCK_IN,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            // Create the stock movement record.
            $operation->stockMovements()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'type' => StockMovementType::IN,
                'notes' => $notes,
            ]);

            // Find or create the balance for this product
            // in this warehouse.
            $balance = InventoryBalance::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            // Increase the stock quantity atomically.
            $balance->increment('quantity', $quantity);

            return $operation->load('stockMovements');
        }, attempts: 3);
    }


    public function stockOut(
        User $user,
        int $productId,
        int $warehouseId,
        string|int|float $quantity,
        ?string $reference = null,
        ?string $notes = null,
    ): InventoryOperation {
        if (! is_numeric($quantity) || (float) $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $productId,
            $warehouseId,
            $quantity,
            $reference,
            $notes,
        ) {
            // Lock the product to serialize stock operations.
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is inactive.',
                ]);
            }

            // Lock the warehouse.
            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($warehouseId);

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'This warehouse is inactive.',
                ]);
            }

            // Check whether this company allows negative stock.
            $allowNegativeStock = $user->company()
                ->with('setting')
                ->firstOrFail()
                ->setting?->allow_negative_stock ?? false;

            // Find or create the inventory balance.
            $balance = InventoryBalance::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            // Prevent stock-out when available quantity is insufficient.
            if (
                ! $allowNegativeStock &&
                (float) $balance->quantity < (float) $quantity
            ) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                    'Insufficient stock. Available quantity: %s.',
                    $balance->quantity
                ),
                ]);
            }

            // Create the parent inventory operation.
            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::STOCK_OUT,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            // Record the stock movement.
            $operation->stockMovements()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'type' => StockMovementType::OUT,
                'notes' => $notes,
            ]);

            // Decrease the inventory balance.
            $balance->decrement('quantity', $quantity);

            return $operation->load('stockMovements');

        }, attempts: 3);
    }

    public function openingBalance(
        User $user,
        int $productId,
        int $warehouseId,
        string|int|float $quantity,
        ?string $reference = null,
        ?string $notes = null,
    ): InventoryOperation {
        if (! is_numeric($quantity) || (float) $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $productId,
            $warehouseId,
            $quantity,
            $reference,
            $notes,
        ) {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($warehouseId);

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'This warehouse is inactive.',
                ]);
            }

            // Prevent duplicate opening balances for this product and warehouse.
            $existingOpeningBalance = StockMovement::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->whereHas('inventoryOperation', function ($query) {
                    $query->where(
                        'type',
                        InventoryOperationType::OPENING_BALANCE->value
                    );
                })
                ->exists();

            if ($existingOpeningBalance) {
                throw ValidationException::withMessages([
                    'product_id' => 'An opening balance has already been recorded for this product in this warehouse.',
                ]);
            }

            // Create the parent inventory operation.
            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::OPENING_BALANCE,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            // Record the opening stock movement.
            $operation->stockMovements()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'type' => StockMovementType::IN,
                'notes' => $notes,
            ]);

            // Find or create the inventory balance.
            $balance = InventoryBalance::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            // Increase the stock quantity.
            $balance->increment('quantity', $quantity);

            return $operation->load('stockMovements');
        }, attempts: 3);
    }

    public function adjust(
        User $user,
        int $productId,
        int $warehouseId,
        string|int|float $countedQuantity,
        string $reason,
        ?string $reference = null,
    ): InventoryOperation {
        if (! is_numeric($countedQuantity) || (float) $countedQuantity < 0) {
            throw ValidationException::withMessages([
                'counted_quantity' => 'The counted quantity cannot be negative.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $productId,
            $warehouseId,
            $countedQuantity,
            $reason,
            $reference,
        ) {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($productId);

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'This product is inactive.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->lockForUpdate()
                ->findOrFail($warehouseId);

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'This warehouse is inactive.',
                ]);
            }

            $balance = InventoryBalance::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            $currentQuantity = (float) $balance->quantity;
            $newQuantity = (float) $countedQuantity;
            $difference = round($newQuantity - $currentQuantity, 4);

            if ($difference == 0.0) {
                throw ValidationException::withMessages([
                    'counted_quantity' => 'The counted quantity matches the current inventory balance. No adjustment is needed.',
                ]);
            }

            $operation = InventoryOperation::create([
                'user_id' => $user->id,
                'type' => InventoryOperationType::ADJUSTMENT,
                'reference' => $reference,
                'notes' => $reason,
            ]);

            $operation->stockMovements()->create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => abs($difference),
                'type' => $difference > 0
                    ? StockMovementType::IN
                    : StockMovementType::OUT,
                'notes' => $reason,
            ]);

            $balance->update([
                'quantity' => $newQuantity,
            ]);

            return $operation->load('stockMovements');
        }, attempts: 3);
    }

    public function getBalances(array $params = [])
    {
        $query = InventoryBalance::query()
            ->with(['product', 'warehouse']);

        // Search by product name or SKU.
        $search = trim($params['search'] ?? '');

        if ($search !== '') {
            $query->whereHas('product', function (Builder $productQuery) use ($search) {
                $productQuery->where(function (Builder $productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Apply filters and sorting.
        QueryFilter::apply(
            query: $query,
            params: $params,
            filterableFields: [
                'product_id',
                'warehouse_id',
            ],
            sortableFields: [
                'quantity',
                'created_at',
            ],
        );

        // Default ordering.
        if (empty($params['sort_by'])) {
            $query->latest('created_at');
        }

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }

    public function getMovements(array $params = [])
    {
        $query = StockMovement::query()
            ->with([
                'product',
                'warehouse',
                'inventoryOperation',
            ]);

        // Filter by product.
        if (! empty($params['product_id'])) {
            $query->where('product_id', $params['product_id']);
        }

        // Filter by warehouse.
        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', $params['warehouse_id']);
        }

        // Filter by movement type.
        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Filter by date range.
        if (! empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        // Search by product name or SKU.
        $search = trim($params['search'] ?? '');

        if ($search !== '') {
            $query->whereHas('product', function (Builder $productQuery) use ($search) {
                $productQuery->where(function (Builder $productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('sku', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Apply sorting.
        QueryFilter::apply(
            query: $query,
            params: $params,
            sortableFields: [
                'created_at',
                'quantity',
            ],
        );

        // Default ordering.
        if (empty($params['sort_by'])) {
            $query->latest('created_at');
        }

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }
}
