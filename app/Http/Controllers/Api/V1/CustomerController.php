<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Customer\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    public function index(CustomerIndexRequest $request)
    {
        $customers = $this->customerService->getCustomers(
            $request->validated()
        );

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): CustomerResource
    {
        $customer = $this->customerService->create(
            $request->validated()
        );

        return new CustomerResource($customer);
    }

    public function show(int $customer)
    {
        return new CustomerResource(
            $this->customerService->show($customer)
        );
    }

    public function update(
        UpdateCustomerRequest $request,
        int $customer,
    ): CustomerResource {
        $updatedCustomer = $this->customerService->update(
            $customer,
            $request->validated()
        );

        return new CustomerResource($updatedCustomer);
    }

    public function destroy(int $customer): JsonResponse
    {
        $this->customerService->delete($customer);

        return response()->json([
            'message' => 'Customer deleted successfully.',
        ]);
    }

    public function activate(int $customer): CustomerResource
    {
        return new CustomerResource(
            $this->customerService->activate($customer)
        );
    }

    public function deactivate(int $customer): CustomerResource
    {
        return new CustomerResource(
            $this->customerService->deactivate($customer)
        );
    }
}