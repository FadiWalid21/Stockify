<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockInRequest;
use App\Http\Requests\StoreStockOutRequest;
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
}
