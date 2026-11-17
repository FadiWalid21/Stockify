<?php

namespace App\Domains\Returns\Services;

use App\Domains\Returns\Enums\SalesReturnStatus;
use App\Domains\Returns\Models\SalesReturnItem;
use App\Domains\Sales\Models\SalesOrderFulfillmentItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;

class SalesReturnEligibilityService
{
    public function forProduct(int $productId): array
    {
        $fulfillmentItems = SalesOrderFulfillmentItem::query()
            ->with([
                'fulfillment.salesOrder.customer',
                'salesOrderItem.product',
            ])
            ->whereHas('fulfillment', function (Builder $query) {
                $query->whereNotNull('dispatched_at');
            })
            ->whereHas('salesOrderItem', function (Builder $query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->get();

        return $fulfillmentItems
            ->map(function (SalesOrderFulfillmentItem $fulfillmentItem) {
                $fulfillment = $fulfillmentItem->fulfillment;
                $salesOrder = $fulfillment->salesOrder;
                $orderItem = $fulfillmentItem->salesOrderItem;

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

                $remaining = BigDecimal::of(
                    (string) $fulfillmentItem->quantity_dispatched
                )->minus($alreadyReturned);

                if ($remaining->isLessThan(BigDecimal::zero())) {
                    $remaining = BigDecimal::zero();
                }

                return [
                    'fulfillment_item_id' => $fulfillmentItem->id,
                    'fulfillment_id' => $fulfillment->id,
                    'sales_order_id' => $salesOrder->id,
                    'sales_order_number' => $salesOrder->order_number,
                    'customer_id' => $salesOrder->customer_id,
                    'customer_name' => $salesOrder->customer->name,
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product->name,
                    'unit_price' => $orderItem->unit_price,
                    'quantity_dispatched' => $fulfillmentItem->quantity_dispatched,
                    'quantity_already_returned' => (string) $alreadyReturned->toScale(
                        4,
                        RoundingMode::HalfUp
                    ),
                    'quantity_returnable' => (string) $remaining->toScale(
                        4,
                        RoundingMode::HalfUp
                    ),
                    'dispatched_at' => $fulfillment->dispatched_at,
                ];
            })
            ->filter(function (array $item) {
                return BigDecimal::of($item['quantity_returnable'])
                    ->isGreaterThan(BigDecimal::zero());
            })
            ->values()
            ->all();
    }
}
