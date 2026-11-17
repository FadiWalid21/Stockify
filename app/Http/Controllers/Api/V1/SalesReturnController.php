<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Returns\Services\SalesReturnEligibilityService;
use App\Domains\Returns\Services\SalesReturnService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReturnActionRequest;
use App\Http\Requests\StoreSalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function __construct(
        private readonly SalesReturnService $salesReturnService,
        private readonly SalesReturnEligibilityService $eligibilityService
    ) {}

    public function index(Request $request)
    {
        $salesReturns = $this->salesReturnService->getSalesReturns(
            $request->only([
                'status',
                'customer_id',
                'sales_order_id',
                'warehouse_id',
                'search',
                'per_page',
            ])
        );

        return SalesReturnResource::collection($salesReturns);
    }

    public function store(StoreSalesReturnRequest $request): SalesReturnResource
    {
        $salesReturn = $this->salesReturnService->createDraft(
            $request->user(),
            $request->validated()
        );

        return new SalesReturnResource($salesReturn);
    }

    public function show(int $salesReturn): SalesReturnResource
    {
        return new SalesReturnResource(
            $this->salesReturnService->show($salesReturn)
        );
    }

    public function eligibleItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        return response()->json([
            'data' => $this->eligibilityService->forProduct(
                (int) $validated['product_id']
            ),
        ]);
    }

    public function approve(
        SalesReturnActionRequest $request,
        int $salesReturn
    ): SalesReturnResource {
        return new SalesReturnResource(
            $this->salesReturnService->approve(
                $request->user(),
                $salesReturn
            )
        );
    }

    public function receive(
        SalesReturnActionRequest $request,
        int $salesReturn
    ): SalesReturnResource {
        return new SalesReturnResource(
            $this->salesReturnService->receive(
                $request->user(),
                $salesReturn
            )
        );
    }

    public function complete(
        SalesReturnActionRequest $request,
        int $salesReturn
    ): SalesReturnResource {
        return new SalesReturnResource(
            $this->salesReturnService->complete($salesReturn)
        );
    }

    public function cancel(
        SalesReturnActionRequest $request,
        int $salesReturn
    ): SalesReturnResource {
        return new SalesReturnResource(
            $this->salesReturnService->cancel($salesReturn)
        );
    }
}
