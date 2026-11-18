<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\ProductPerformanceReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\ProductPerformanceReportRequest;
use Illuminate\Http\JsonResponse;

class ProductPerformanceReportController extends Controller
{
    public function __construct(
        private ProductPerformanceReportService $productPerformanceReportService
    ) {}

    public function topSelling(
        ProductPerformanceReportRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'Product performance report retrieved successfully.',
            'data' => $this->productPerformanceReportService->topSelling(
                $request->validated()
            ),
        ]);
    }
}