<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\InventoryReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\InventoryReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(
        private InventoryReportService $inventoryReportService
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json([
            'message' => 'Inventory summary report retrieved successfully.',
            'data' => $this->inventoryReportService->summary(),
        ]);
    }

    public function byWarehouse(): JsonResponse
    {
        return response()->json([
            'message' => 'Inventory warehouse report retrieved successfully.',
            'data' => $this->inventoryReportService->byWarehouse(),
        ]);
    }

    public function products(InventoryReportRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Inventory product report retrieved successfully.',
            'data' => $this->inventoryReportService->products(
                $request->validated()
            ),
        ]);
    }
}