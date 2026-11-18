<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\PurchaseReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\PurchaseReportRequest;
use Illuminate\Http\JsonResponse;

class PurchaseReportController extends Controller
{
    public function __construct(
        private PurchaseReportService $purchaseReportService
    ) {}

    public function summary(PurchaseReportRequest $request): JsonResponse
    {
        $report = $this->purchaseReportService->summary(
            $request->validated()
        );

        return response()->json([
            'message' => 'Purchase summary report retrieved successfully.',
            'data' => $report,
        ]);
    }
}