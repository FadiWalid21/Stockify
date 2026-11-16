<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Supplier\Models\Supplier;
use App\Domains\Supplier\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\SupplierIndexRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    public function index(SupplierIndexRequest $request): AnonymousResourceCollection
    {
        $suppliers = $this->supplierService->getSuppliers(
            $request->validated()
        );

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $supplier = $this->supplierService->createSupplier(
            $request->validated()
        );

        return new SupplierResource($supplier);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ): SupplierResource {
        $supplier = $this->supplierService->updateSupplier(
            $supplier,
            $request->validated()
        );

        return new SupplierResource($supplier);
    }

    public function destroy(Supplier $supplier): Response
    {
        $this->supplierService->deleteSupplier($supplier);

        return response()->noContent();
    }

    public function activate(Supplier $supplier): SupplierResource
    {
        return new SupplierResource(
            $this->supplierService->activateSupplier($supplier)
        );
    }

    public function deactivate(Supplier $supplier): SupplierResource
    {
        return new SupplierResource(
            $this->supplierService->deactivateSupplier($supplier)
        );
    }
}
