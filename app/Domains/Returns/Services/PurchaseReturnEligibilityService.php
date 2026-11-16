<?php

namespace App\Domains\Returns\Services;

use App\Domains\Purchase\Models\PurchaseOrderReceiptItem;
use App\Domains\Returns\Enums\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReturnEligibilityService
{
    public function forProduct(int $productId): array
    {
        $receiptItems = PurchaseOrderReceiptItem::query()
            ->with([
                'receipt.purchaseOrder.supplier',
                'purchaseOrderItem.product',
            ])
            ->whereHas('purchaseOrderItem', function (Builder $query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->get();

        return $receiptItems
            ->map(function (PurchaseOrderReceiptItem $receiptItem) {
                $purchaseOrder = $receiptItem->receipt->purchaseOrder;
                $purchaseOrderItem = $receiptItem->purchaseOrderItem;

                $alreadyReturned = \App\Domains\Returns\Models\PurchaseReturnItem::query()
                    ->where('purchase_order_receipt_item_id', $receiptItem->id)
                    ->whereHas('purchaseReturn', function (Builder $query) {
                        $query->where(
                            'status',
                            '!=',
                            PurchaseReturnStatus::CANCELLED->value
                        );
                    })
                    ->sum('quantity');

                $remaining = max(
                    0,
                    (float) $receiptItem->quantity_received - (float) $alreadyReturned
                );

                return [
                    'receipt_item_id' => $receiptItem->id,
                    'receipt_id' => $receiptItem->purchase_order_receipt_id,
                    'purchase_order_id' => $receiptItem->purchase_order_id,
                    'purchase_order_item_id' => $receiptItem->purchase_order_item_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'supplier_name' => $purchaseOrder->supplier->name,
                    'purchase_order_number' => $purchaseOrder->order_number,
                    'product_id' => $purchaseOrderItem->product_id,
                    'product_name' => $purchaseOrderItem->product->name,
                    'unit_cost' => $purchaseOrderItem->unit_cost,
                    'quantity_received' => $receiptItem->quantity_received,
                    'quantity_already_returned' => $alreadyReturned,
                    'quantity_returnable' => number_format($remaining, 4, '.', ''),
                ];
            })
            ->filter(fn (array $item) => (float) $item['quantity_returnable'] > 0)
            ->values()
            ->all();
    }
}
