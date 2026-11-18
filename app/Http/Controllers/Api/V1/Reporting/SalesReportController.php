<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Domains\Reporting\Services\SalesReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reporting\SalesReportRequest;
use Illuminate\Http\JsonResponse;

class SalesReportController extends Controller
{
    public function __construct(
        private SalesReportService $salesReportService
    ) {}

    public function revenue(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->revenue(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales revenue report retrieved successfully.',
            'data' => $report,
        ]);
    }

    public function byCustomer(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->byCustomer(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales by customer report retrieved successfully.',
            'data' => $report,
        ]);
    }

    public function byProduct(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->byProduct(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales by product report retrieved successfully.',
            'data' => $report,
        ]);
    }

    public function invoiceStatus(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->invoiceStatus(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales invoice status report retrieved successfully.',
            'data' => $report,
        ]);
    }

    public function salesTrend(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->salesTrend(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales trend report retrieved successfully.',
            'data' => $report,
        ]);
    }

    public function salesReturns(SalesReportRequest $request): JsonResponse
    {
        $report = $this->salesReportService->salesReturns(
            $request->validated()
        );

        return response()->json([
            'message' => 'Sales returns report retrieved successfully.',
            'data' => $report,
        ]);
    }
}