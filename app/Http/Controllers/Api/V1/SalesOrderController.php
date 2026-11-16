<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Sales\Services\SalesOrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\DispatchSalesOrderRequest;
use App\Http\Requests\SalesOrderIndexRequest;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function __construct(
        private SalesOrderService $salesOrderService,
    ) {}

    public function index(
        SalesOrderIndexRequest $request,
    ): AnonymousResourceCollection {
        $orders = $this->salesOrderService->getSalesOrders(
            $request->validated()
        );

        return SalesOrderResource::collection($orders);
    }

    public function store(
        StoreSalesOrderRequest $request,
    ): SalesOrderResource {
        $order = $this->salesOrderService->createDraft(
            $request->user(),
            $request->validated(),
        );

        return new SalesOrderResource($order);
    }

    public function show(int $salesOrderId): SalesOrderResource
    {
        return new SalesOrderResource(
            $this->salesOrderService->show($salesOrderId)
        );
    }

    public function update(
        UpdateSalesOrderRequest $request,
        int $salesOrderId,
    ): SalesOrderResource {
        $order = $this->salesOrderService->updateDraft(
            $request->user(),
            $salesOrderId,
            $request->validated(),
        );

        return new SalesOrderResource($order);
    }

    public function place(
        Request $request,
        int $salesOrderId,
    ): SalesOrderResource {
        return new SalesOrderResource(
            $this->salesOrderService->place(
                $request->user(),
                $salesOrderId,
            )
        );
    }

    public function confirm(
        Request $request,
        int $salesOrderId,
    ): SalesOrderResource {
        return new SalesOrderResource(
            $this->salesOrderService->confirm(
                $request->user(),
                $salesOrderId,
            )
        );
    }

    public function cancel(
        Request $request,
        int $salesOrderId,
    ): SalesOrderResource {
        return new SalesOrderResource(
            $this->salesOrderService->cancel(
                $request->user(),
                $salesOrderId,
            )
        );
    }

    public function dispatch(
        DispatchSalesOrderRequest $request,
        int $salesOrderId,
    ): SalesOrderResource {
        return new SalesOrderResource(
            $this->salesOrderService->dispatch(
                $request->user(),
                $salesOrderId,
                $request->validated(),
            )
        );
    }
}