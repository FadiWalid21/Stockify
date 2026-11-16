<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Purchase\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrderIndexRequest;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Domains\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(
        PurchaseOrderIndexRequest $request,
    ): AnonymousResourceCollection {
        $purchaseOrders = $this->purchaseOrderService->getPurchaseOrders(
            $request->validated()
        );

        return PurchaseOrderResource::collection($purchaseOrders);
    }

    public function store(StorePurchaseOrderRequest $request): PurchaseOrderResource
    {
        $purchaseOrder = $this->purchaseOrderService->createDraft(
            $request->user(),
            $request->validated()
        );

        return new PurchaseOrderResource($purchaseOrder);
    }

    public function show(int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource(
            $this->purchaseOrderService->show($purchaseOrder)
        );
    }

    public function update(
        UpdatePurchaseOrderRequest $request,
        int $purchaseOrder,
    ): PurchaseOrderResource {
        $updatedPurchaseOrder = $this->purchaseOrderService->updateDraft(
            $purchaseOrder,
            $request->validated()
        );

        return new PurchaseOrderResource($updatedPurchaseOrder);
    }

    public function approve(Request $request, int $purchaseOrder): PurchaseOrderResource
    {
        $approvedPurchaseOrder = $this->purchaseOrderService->approve(
            $request->user(),
            $purchaseOrder
        );

        return new PurchaseOrderResource($approvedPurchaseOrder);
    }

    public function cancel(Request $request, int $purchaseOrder): PurchaseOrderResource
    {
        $cancelledPurchaseOrder = $this->purchaseOrderService->cancel(
            $request->user(),
            $purchaseOrder
        );

        return new PurchaseOrderResource($cancelledPurchaseOrder);
    }

    public function receive(
        ReceivePurchaseOrderRequest $request,
        int $purchaseOrder,
    ): PurchaseOrderResource {
        $receivedPurchaseOrder = $this->purchaseOrderService->receive(
            $request->user(),
            $purchaseOrder,
            $request->validated()
        );

        return new PurchaseOrderResource($receivedPurchaseOrder);
    }
}