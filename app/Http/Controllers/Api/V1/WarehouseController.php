<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Warehouse\Models\Warehouse;
use App\Domains\Warehouse\Services\WarehouseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Requests\WarehouseIndexRequest;
use App\Http\Resources\WarehouseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public function index(WarehouseIndexRequest $request)
    {
        $warehouses = $this->warehouseService->getAll(
            $request->validated()
        );

        return WarehouseResource::collection($warehouses);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create(
            $request->validated()
        );

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Warehouse $warehouse): WarehouseResource
    {
        return new WarehouseResource($warehouse);
    }

    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $warehouse
    ): WarehouseResource {
        $warehouse = $this->warehouseService->update(
            $warehouse,
            $request->validated()
        );

        return new WarehouseResource($warehouse);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->warehouseService->delete($warehouse);

        return response()->json([
            'message' => 'Warehouse deleted successfully.',
        ]);
    }

    public function deactivate(Warehouse $warehouse): WarehouseResource
    {
        $warehouse = $this->warehouseService->deactivate($warehouse);

        return new WarehouseResource($warehouse);
    }

    public function activate(Warehouse $warehouse): WarehouseResource
    {
        $warehouse = $this->warehouseService->activate($warehouse);

        return new WarehouseResource($warehouse);
    }
}