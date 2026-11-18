<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\FinancialSummaryReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\FinancialSummaryReportRequest;
use Illuminate\Http\JsonResponse;

class FinancialSummaryReportController extends Controller
{
    public function __construct(
        private FinancialSummaryReportService $financialSummaryReportService
    ) {}

    public function summary(
        FinancialSummaryReportRequest $request
    ): JsonResponse {
        $report = $this->financialSummaryReportService->summary(
            $request->validated()
        );

        return response()->json([
            'message' => 'Financial summary retrieved successfully.',
            'data' => $report,
        ]);
    }
}