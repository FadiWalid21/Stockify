<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\CustomerReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\CustomerReportRequest;
use Illuminate\Http\JsonResponse;

class CustomerReportController extends Controller
{
    public function __construct(
        private CustomerReportService $customerReportService
    ) {}

    public function salesSummary(
        CustomerReportRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'Customer sales report retrieved successfully.',
            'data' => $this->customerReportService->salesSummary(
                $request->validated()
            ),
        ]);
    }
}