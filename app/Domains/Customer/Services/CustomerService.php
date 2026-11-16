<?php

namespace App\Domains\Customer\Services;

use App\Domains\Customer\Models\Customer;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function getCustomers(array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query();

        $query = QueryFilter::apply(
            $query,
            $filters,
            ['name', 'email', 'phone', 'tax_number', 'contact_person'],
            [],
            ['name', 'email', 'created_at'],
        );

        $status = $filters['status'] ?? 'active';

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (! isset($filters['sort_by'])) {
            $query->latest();
        }

        return $query->paginate(
            Pagination::perPage($filters['per_page'] ?? null)
        );
    }

    public function create(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    public function show(int $customerId): Customer
    {
        return Customer::query()->findOrFail($customerId);
    }

    public function update(int $customerId, array $data): Customer
    {
        $customer = Customer::query()->findOrFail($customerId);

        $customer->update($data);

        return $customer->refresh();
    }

    public function delete(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);

        $customer->delete();
    }

    public function activate(int $customerId): Customer
    {
        $customer = Customer::query()->findOrFail($customerId);

        $customer->update(['is_active' => true]);

        return $customer->refresh();
    }

    public function deactivate(int $customerId): Customer
    {
        $customer = Customer::query()->findOrFail($customerId);

        $customer->update(['is_active' => false]);

        return $customer->refresh();
    }
}