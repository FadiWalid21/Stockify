<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sales\Services\SalesInvoiceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private SalesInvoiceService $salesInvoiceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->salesInvoiceService->getInvoices(
            $request->all()
        );

        return response()->json($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales_order_id' => ['required', 'integer', 'min:1'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_rate' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],
            'items.*.tax_rate' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],
        ]);

        $invoice = $this->salesInvoiceService->createDraft(
            $request->user(),
            $validated['sales_order_id'],
            $validated
        );

        return response()->json([
            'message' => 'Sales invoice draft created successfully.',
            'data' => $invoice,
        ], 201);
    }

    public function show(int $invoiceId): JsonResponse
    {
        return response()->json([
            'data' => $this->salesInvoiceService->show($invoiceId),
        ]);
    }

    public function issue(int $invoiceId): JsonResponse
    {
        $invoice = $this->salesInvoiceService->issue($invoiceId);

        return response()->json([
            'message' => 'Sales invoice issued successfully.',
            'data' => $invoice,
        ]);
    }

    public function cancel(Request $request, int $invoiceId): JsonResponse
    {
        $invoice = $this->salesInvoiceService->cancelDraft(
            $request->user(),
            $invoiceId
        );

        return response()->json([
            'message' => 'Sales invoice cancelled successfully.',
            'data' => $invoice,
        ]);
    }
}