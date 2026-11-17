<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sales\Services\SalesPaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesPaymentController extends Controller
{
    public function __construct(
        private SalesPaymentService $paymentService
    ) {}


    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getPayments(
            $request->all()
        );

        return response()->json([
            'data' => $payments,
        ]);
    }

    public function show(int $paymentId): JsonResponse
    {
        $payment = $this->paymentService->show($paymentId);

        return response()->json([
            'data' => $payment,
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.sales_invoice_id' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
            'allocations.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $payment = $this->paymentService->create(
            $request->user(),
            $validated
        );

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data' => $payment,
        ], 201);
    }


    public function cancel(int $paymentId): JsonResponse
    {
        $payment = $this->paymentService->cancel($paymentId);

        return response()->json([
            'message' => 'Payment cancelled successfully.',
            'data' => $payment,
        ]);
    }


    public function allocate(
        Request $request,
        int $paymentId
    ): JsonResponse {
        $validated = $request->validate([
            'sales_invoice_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $payment = $this->paymentService->allocate(
            $paymentId,
            $validated['sales_invoice_id'],
            (string) $validated['amount']
        );

        return response()->json([
            'message' => 'Payment allocated successfully.',
            'data' => $payment,
        ]);
    }

}