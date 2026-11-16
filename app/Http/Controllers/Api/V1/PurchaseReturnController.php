<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Returns\Services\PurchaseReturnEligibilityService;
use App\Domains\Returns\Services\PurchaseReturnService;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexPurchaseReturnRequest;
use App\Http\Requests\StorePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnService $purchaseReturnService,
        private readonly PurchaseReturnEligibilityService $eligibilityService,
    ) {}

    public function index(
        IndexPurchaseReturnRequest $request
    ): AnonymousResourceCollection {
        $returns = $this->purchaseReturnService->getPurchaseReturns(
            $request->validated()
        );

        return PurchaseReturnResource::collection($returns);
    }

    public function store(
        StorePurchaseReturnRequest $request
    ): PurchaseReturnResource {
        $purchaseReturn = $this->purchaseReturnService->createDraft(
            $request->user(),
            $request->validated()
        );

        return new PurchaseReturnResource($purchaseReturn);
    }

    public function show(int $purchaseReturnId): PurchaseReturnResource
    {
        return new PurchaseReturnResource(
            $this->purchaseReturnService->show($purchaseReturnId)
        );
    }

    public function approve(
        Request $request,
        int $purchaseReturnId
    ): PurchaseReturnResource {
        return new PurchaseReturnResource(
            $this->purchaseReturnService->approve(
                $request->user(),
                $purchaseReturnId
            )
        );
    }

    public function dispatch(
        Request $request,
        int $purchaseReturnId
    ): PurchaseReturnResource {
        return new PurchaseReturnResource(
            $this->purchaseReturnService->dispatch(
                $request->user(),
                $purchaseReturnId
            )
        );
    }

    public function cancel(
        Request $request,
        int $purchaseReturnId
    ): PurchaseReturnResource {
        return new PurchaseReturnResource(
            $this->purchaseReturnService->cancel(
                $request->user(),
                $purchaseReturnId
            )
        );
    }

    public function eligibility(int $productId): JsonResponse
    {
        return response()->json([
            'data' => $this->eligibilityService->forProduct($productId),
        ]);
    }
}
