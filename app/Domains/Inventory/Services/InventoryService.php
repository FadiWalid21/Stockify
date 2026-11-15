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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

}
