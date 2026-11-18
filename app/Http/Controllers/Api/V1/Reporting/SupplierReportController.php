<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\SupplierReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\SupplierReportRequest;
use Illuminate\Http\JsonResponse;

class SupplierReportController extends Controller
{
    public function __construct(
        private SupplierReportService $supplierReportService
    ) {}

    public function purchaseSummary(
        SupplierReportRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'Supplier purchase report retrieved successfully.',
            'data' => $this->supplierReportService->purchaseSummary(
                $request->validated()
            ),
        ]);
    }
}