<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryBalanceIndexRequest;
use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\StoreOpeningBalanceRequest;
use App\Http\Requests\StoreStockInRequest;
use App\Http\Requests\StoreStockOutRequest;
use App\Http\Resources\InventoryBalanceResource;
use App\Http\Resources\InventoryOperationResource;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function stockIn(StoreStockInRequest $request): JsonResponse
    {
        $operation = $this->inventoryService->stockIn(
            user: $request->user(),
            productId: $request->validated('product_id'),
            warehouseId: $request->validated('warehouse_id'),
            quantity: $request->validated('quantity'),
            reference: $request->validated('reference'),
            notes: $request->validated('notes'),
        );

        return response()->json([
            'message' => 'Stock added successfully.',
            'data' => new InventoryOperationResource($operation),
        ], 201);
    }

    public function stockOut(StoreStockOutRequest $request): JsonResponse
    {
        $operation = $this->inventoryService->stockOut(
            user: $request->user(),
            productId: $request->validated('product_id'),
            warehouseId: $request->validated('warehouse_id'),
            quantity: $request->validated('quantity'),
            reference: $request->validated('reference'),
            notes: $request->validated('notes'),
        );

        return response()->json([
            'message' => 'Stock removed successfully.',
            'data' => new InventoryOperationResource($operation),
        ], 201);
    }

    public function openingBalance(StoreOpeningBalanceRequest $request): JsonResponse
    {
        $operation = $this->inventoryService->openingBalance(
            user: $request->user(),
            productId: $request->validated('product_id'),
            warehouseId: $request->validated('warehouse_id'),
            quantity: $request->validated('quantity'),
            reference: $request->validated('reference'),
            notes: $request->validated('notes'),
        );

        return response()->json([
            'message' => 'Opening balance recorded successfully.',
            'data' => new InventoryOperationResource($operation),
        ], 201);
    }

    public function adjust(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $operation = $this->inventoryService->adjust(
            user: $request->user(),
            productId: $request->validated('product_id'),
            warehouseId: $request->validated('warehouse_id'),
            countedQuantity: $request->validated('counted_quantity'),
            reason: $request->validated('reason'),
            reference: $request->validated('reference'),
        );

        return response()->json([
            'message' => 'Inventory adjusted successfully.',
            'data' => new InventoryOperationResource($operation),
        ], 201);
    }

    public function balances(InventoryBalanceIndexRequest $request): JsonResponse
    {
        $balances = $this->inventoryService->getBalances(
            $request->validated()
        );

        return response()->json([
            'message' => 'Inventory balances retrieved successfully.',
            'data' => InventoryBalanceResource::collection($balances),
            'meta' => [
                'current_page' => $balances->currentPage(),
                'last_page' => $balances->lastPage(),
                'per_page' => $balances->perPage(),
                'total' => $balances->total(),
            ],
        ]);
    }
}
