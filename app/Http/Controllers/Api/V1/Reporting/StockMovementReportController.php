<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\StockMovementReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\StockMovementReportRequest;
use Illuminate\Http\JsonResponse;

class StockMovementReportController extends Controller
{
    public function __construct(
        private StockMovementReportService $stockMovementReportService
    ) {}

    public function summary(StockMovementReportRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Stock movement summary report retrieved successfully.',
            'data' => $this->stockMovementReportService->summary(
                $request->validated()
            ),
        ]);
    }

    public function movements(StockMovementReportRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Stock movement history retrieved successfully.',
            'data' => $this->stockMovementReportService->movements(
                $request->validated()
            ),
        ]);
    }
}