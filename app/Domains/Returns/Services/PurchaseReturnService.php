<?php

namespace App\Domains\Returns\Services;

use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Product\Models\Product;
use App\Domains\Purchase\Models\PurchaseOrderReceiptItem;
use App\Domains\Returns\Enums\PurchaseReturnStatus;
use App\Domains\Returns\Models\PurchaseReturn;
use App\Domains\Supplier\Models\Supplier;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseReturnService
{
    public function __construct(
        private readonly PurchaseReturnNumberGenerator $numberGenerator,
        private readonly InventoryService $inventoryService,
    ) {}
    public function createDraft(User $user, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $data) {
            $companyId = $user->company_id;

            $supplier = Supplier::query()
                ->whereKey($data['supplier_id'])
                ->firstOrFail();

            $warehouse = Warehouse::query()
                ->whereKey($data['warehouse_id'])
                ->firstOrFail();

            if (! $supplier->is_active) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'The selected supplier is inactive.',
                ]);
            }

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The selected warehouse is inactive.',
                ]);
            }

            if (empty($data['items']) || ! is_array($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'At least one return item is required.',
                ]);
            }

            $hasManualItems = collect($data['items'])->contains(
                    fn (array $item) => empty($item['purchase_order_receipt_item_id'])
                );

                if ($hasManualItems && blank($data['reason'] ?? null)) {
                    throw ValidationException::withMessages([
                        'reason' => 'A reason is required when creating manual purchase return items.',
                    ]);
                }

            $purchaseReturn = PurchaseReturn::query()->create([
                'company_id' => $companyId,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'return_number' => $this->numberGenerator->next($companyId),
                'status' => PurchaseReturnStatus::DRAFT,
                'return_date' => $data['return_date'],
                'subtotal' => 0,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $pendingReceiptQuantities = [];

            foreach ($data['items'] as $index => $item) {
                $receiptItemId = $item['purchase_order_receipt_item_id'] ?? null;

                if ($receiptItemId !== null) {
                    $receiptItem = PurchaseOrderReceiptItem::query()
                        ->with([
                            'receipt.purchaseOrder.supplier',
                            'purchaseOrderItem.product',
                        ])
                        ->lockForUpdate()
                        ->findOrFail($receiptItemId);

                    $receipt = $receiptItem->receipt;
                    $purchaseOrder = $receipt->purchaseOrder;
                    $purchaseOrderItem = $receiptItem->purchaseOrderItem;

                    if ((int) $purchaseOrder->supplier_id !== (int) $supplier->id) {
                        throw ValidationException::withMessages([
                            "items.{$index}.purchase_order_receipt_item_id"
                                => 'This receipt belongs to a different supplier.',
                        ]);
                    }

                    $quantity = (float) $item['quantity'];

                    if ($quantity <= 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity"
                                => 'Quantity must be greater than zero.',
                        ]);
                    }

                    // Sum previous returns through Eloquent so the
                    // relationship status filter is applied correctly.
                    $alreadyReturnedQuantity = \App\Domains\Returns\Models\PurchaseReturnItem::query()
                        ->where('purchase_order_receipt_item_id', $receiptItem->id)
                        ->whereHas('purchaseReturn', function ($query) {
                            $query->where(
                                'status',
                                '!=',
                                PurchaseReturnStatus::CANCELLED->value
                            );
                        })
                        ->sum('quantity');

                    $pendingQuantity = $pendingReceiptQuantities[$receiptItem->id] ?? 0;

                    $availableQuantity =
                        (float) $receiptItem->quantity_received
                        - (float) $alreadyReturnedQuantity
                        - $pendingQuantity;

                    if ($quantity > $availableQuantity + 0.0000001) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity"
                                => "Return quantity exceeds the remaining received quantity ({$availableQuantity}).",
                        ]);
                    }

                    $pendingReceiptQuantities[$receiptItem->id] =
                        $pendingQuantity + $quantity;

                    $product = $purchaseOrderItem->product;
                    $unitCost = (float) $purchaseOrderItem->unit_cost;

                    $lineTotal = round($quantity * $unitCost, 4);

                    $purchaseReturn->items()->create([
                        'company_id' => $companyId,
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_order_item_id' => $purchaseOrderItem->id,
                        'purchase_order_receipt_id' => $receipt->id,
                        'purchase_order_receipt_item_id' => $receiptItem->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                } else {
                    $product = Product::query()
                        ->whereKey($item['product_id'])
                        ->firstOrFail();

                    $quantity = (float) $item['quantity'];
                    $unitCost = (float) $item['unit_cost'];

                    if ($quantity <= 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity"
                                => 'Quantity must be greater than zero.',
                        ]);
                    }

                    if ($unitCost < 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.unit_cost"
                                => 'Unit cost cannot be negative.',
                        ]);
                    }

                    $lineTotal = round($quantity * $unitCost, 4);

                    $purchaseReturn->items()->create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }

                $subtotal += $lineTotal;
            }

            $purchaseReturn->update([
                'subtotal' => round($subtotal, 4),
            ]);

            return $purchaseReturn->load([
                'supplier',
                'warehouse',
                'user',
                'items.product',
                'items.purchaseOrder',
                'items.purchaseOrderReceipt',
            ]);
        }, attempts: 3);
    }

    public function approve(User $user, int $purchaseReturnId): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $purchaseReturnId) {
            $purchaseReturn = PurchaseReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseReturnId);

            if ($purchaseReturn->status !== PurchaseReturnStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft purchase returns can be approved.',
                ]);
            }

            if ($purchaseReturn->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'A purchase return must contain at least one item.',
                ]);
            }

            $purchaseReturn->update([
                'status' => PurchaseReturnStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $purchaseReturn->fresh([
                'supplier',
                'warehouse',
                'user',
                'approvedBy',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function dispatch(User $user, int $purchaseReturnId): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $purchaseReturnId) {
            $purchaseReturn = PurchaseReturn::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseReturnId);

            if ($purchaseReturn->status !== PurchaseReturnStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved purchase returns can be dispatched.',
                ]);
            }

            foreach ($purchaseReturn->items as $item) {
                $this->inventoryService->stockOut(
                    user: $user,
                    productId: $item->product_id,
                    warehouseId: $purchaseReturn->warehouse_id,
                    quantity: $item->quantity,
                    reference: $purchaseReturn->return_number,
                    notes: 'Purchase return to supplier.',
                );
            }

            $purchaseReturn->update([
                'status' => PurchaseReturnStatus::COMPLETED,
                'dispatched_by' => $user->id,
                'dispatched_at' => now(),
                'completed_at' => now(),
            ]);

            return $purchaseReturn->fresh([
                'supplier',
                'warehouse',
                'user',
                'approvedBy',
                'dispatchedBy',
                'items.product',
            ]);
        }, attempts: 3);
    }

    public function cancel(User $user, int $purchaseReturnId): PurchaseReturn
    {
        return DB::transaction(function () use ($user, $purchaseReturnId) {
            $purchaseReturn = PurchaseReturn::query()
                ->lockForUpdate()
                ->findOrFail($purchaseReturnId);

            if (! in_array($purchaseReturn->status, [
                PurchaseReturnStatus::DRAFT,
                PurchaseReturnStatus::APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft or approved purchase returns can be cancelled.',
                ]);
            }

            $purchaseReturn->update([
                'status' => PurchaseReturnStatus::CANCELLED,
            ]);

            return $purchaseReturn->fresh([
                'supplier',
                'warehouse',
                'user',
                'items.product',
            ]);
        }, attempts: 3);
    }


    public function getPurchaseReturns(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return PurchaseReturn::query()
            ->with([
                'supplier',
                'warehouse',
                'user',
                'approvedBy',
                'dispatchedBy',
                'items.product',
                'items.purchaseOrder',
            ])
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['supplier_id'] ?? null,
                fn ($query, $supplierId) => $query->where('supplier_id', $supplierId)
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
                            ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                                $supplierQuery->where('name', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(int $purchaseReturnId): PurchaseReturn
    {
        return PurchaseReturn::query()
            ->with([
                'supplier',
                'purchaseOrder',
                'warehouse',
                'user',
                'approvedBy',
                'dispatchedBy',
                'items.product',
                'items.purchaseOrder',
                'items.purchaseOrderItem',
                'items.purchaseOrderReceipt',
                'items.purchaseOrderReceiptItem',
            ])
            ->findOrFail($purchaseReturnId);
    }

}
