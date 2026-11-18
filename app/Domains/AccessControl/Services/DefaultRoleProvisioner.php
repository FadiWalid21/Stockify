<?php

namespace App\Domains\AccessControl\Services;

use App\Domains\AccessControl\Models\Permission;
use App\Domains\Company\Models\Company;
use RuntimeException;

class DefaultRoleProvisioner
{
    public function provision(Company $company): void
    {
        $permissionIdsByKey = Permission::query()
            ->pluck('id', 'key');

        $roles = [
            'owner' => [
                'name' => 'Owner',
                'permissions' => [],
            ],

            'admin' => [
                'name' => 'Admin',
                'permissions' => $permissionIdsByKey->keys()->all(),
            ],

            'inventory_manager' => [
                'name' => 'Inventory Manager',
                'permissions' => [
                    'products.view',
                    'products.create',
                    'products.update',
                    'products.delete',

                    'categories.view',
                    'categories.manage',

                    'warehouses.view',
                    'warehouses.manage',

                    'inventory.view',
                    'inventory.stock_in',
                    'inventory.stock_out',
                    'inventory.adjust',

                    'transfers.view',
                    'transfers.create',
                    'transfers.approve',
                    'transfers.dispatch',
                    'transfers.receive',

                    'suppliers.view',
                    'suppliers.create',
                    'suppliers.update',
                    'suppliers.delete',

                    'purchase_orders.view',
                    'purchase_orders.create',
                    'purchase_orders.update',
                    'purchase_orders.approve',
                    'purchase_orders.receive',
                    'purchase_orders.cancel',

                    'purchase_returns.view',
                    'purchase_returns.create',
                    'purchase_returns.approve',
                    'purchase_returns.dispatch',
                    'purchase_returns.cancel',

                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'customers.delete',
                    'customers.activate',
                    'customers.deactivate',

                    'sales_orders.view',
                    'sales_orders.create',
                    'sales_orders.update',
                    'sales_orders.place',
                    'sales_orders.confirm',
                    'sales_orders.cancel',
                    'sales_orders.dispatch',

                    'sales_returns.view',
                    'sales_returns.create',
                    'sales_returns.approve',
                    'sales_returns.receive',
                    'sales_returns.complete',
                    'sales_returns.cancel',

                    'sales_invoices.view',
                    'sales_invoices.create',
                    'sales_invoices.issue',
                    'sales_invoices.cancel',

                    'sales_payments.view',
                    'sales_payments.create',
                    'sales_payments.cancel',

                    'reports.sales.view',
                    'reports.purchases.view',
                    'reports.inventory.view',
                    'reports.financial.view',
                ],
            ],

            'warehouse_staff' => [
                'name' => 'Warehouse Staff',
                'permissions' => [
                    'products.view',
                    'categories.view',
                    'warehouses.view',
                    'inventory.view',
                    'inventory.stock_in',
                    'inventory.stock_out',
                    'transfers.view',
                    'transfers.dispatch',
                    'transfers.receive',
                    'purchase_orders.view',
                    'purchase_orders.receive',
                    'purchase_returns.view',
                    'sales_orders.view',
                    'sales_orders.dispatch',
                    'sales_returns.view',
                    'sales_returns.receive',
                ],
            ],
        ];

        foreach ($roles as $key => $data) {
            $role = $company->roles()->create([
                'name' => $data['name'],
                'key' => $key,
            ]);

            if (empty($data['permissions'])) {
                continue;
            }

            $missingPermissions = array_diff(
                $data['permissions'],
                $permissionIdsByKey->keys()->all()
            );

            if (! empty($missingPermissions)) {
                throw new RuntimeException(
                    'Missing permissions: ' . implode(', ', $missingPermissions)
                );
            }

            $permissionIds = $permissionIdsByKey
                ->only($data['permissions'])
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
