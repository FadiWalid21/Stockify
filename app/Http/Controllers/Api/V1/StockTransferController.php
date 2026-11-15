<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Transfer\Services\StockTransferService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\StockTransferIndexRequest;
use App\Http\Resources\StockTransferResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockTransferController extends Controller
{
    public function __construct(
        private StockTransferService $transferService
    ) {}

    public function index(StockTransferIndexRequest $request)
    {
        $transfers = $this->transferService->getTransfers(
            $request->validated()
        );

        return StockTransferResource::collection($transfers);
    }

    public function store(StoreStockTransferRequest $request)
    {
        $transfer = $this->transferService->createDraft(
            $request->user(),
            $request->integer('from_warehouse_id'),
            $request->integer('to_warehouse_id'),
            $request->validated('items'),
            $request->validated('reference'),
            $request->validated('notes'),
        );

        return (new StockTransferResource($transfer))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $transferId)
    {
        return new StockTransferResource(
            $this->transferService->show($transferId)
        );
    }

    public function approve(Request $request, int $transferId)
    {
        $transfer = $this->transferService->approve(
            $request->user(),
            $transferId
        );

        return new StockTransferResource($transfer);
    }

    public function dispatch(Request $request, int $transferId)
    {
        $transfer = $this->transferService->dispatch(
            $request->user(),
            $transferId
        );

        return new StockTransferResource($transfer);
    }

    public function receive(Request $request, int $transferId)
    {
        $transfer = $this->transferService->receive(
            $request->user(),
            $transferId
        );

        return new StockTransferResource($transfer);
    }

    public function cancel(Request $request, int $transferId)
    {
        $transfer = $this->transferService->cancel(
            $request->user(),
            $transferId
        );

        return new StockTransferResource($transfer);
    }
}