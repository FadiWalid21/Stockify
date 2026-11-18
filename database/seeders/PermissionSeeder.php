<?php

namespace Database\Seeders;

use App\Domains\AccessControl\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Products
            ['name' => 'View Products', 'key' => 'products.view'],
            ['name' => 'Create Products', 'key' => 'products.create'],
            ['name' => 'Update Products', 'key' => 'products.update'],
            ['name' => 'Delete Products', 'key' => 'products.delete'],

            // Categories
            ['name' => 'View Categories', 'key' => 'categories.view'],
            ['name' => 'Manage Categories', 'key' => 'categories.manage'],

            // Warehouses
            ['name' => 'View Warehouses', 'key' => 'warehouses.view'],
            ['name' => 'Manage Warehouses', 'key' => 'warehouses.manage'],

            // Inventory
            ['name' => 'View Inventory', 'key' => 'inventory.view'],
            ['name' => 'Stock In', 'key' => 'inventory.stock_in'],
            ['name' => 'Stock Out', 'key' => 'inventory.stock_out'],
            ['name' => 'Adjust Inventory', 'key' => 'inventory.adjust'],

            // Transfers
            ['name' => 'View Transfers', 'key' => 'transfers.view'],
            ['name' => 'Create Transfers', 'key' => 'transfers.create'],
            ['name' => 'Approve Transfers', 'key' => 'transfers.approve'],
            ['name' => 'Dispatch Transfers', 'key' => 'transfers.dispatch'],
            ['name' => 'Receive Transfers', 'key' => 'transfers.receive'],

            // Users and access control
            ['name' => 'View Users', 'key' => 'users.view'],
            ['name' => 'Manage Users', 'key' => 'users.manage'],
            ['name' => 'Manage Roles', 'key' => 'roles.manage'],

            // Suppliers
            ['name' => 'View Suppliers', 'key' => 'suppliers.view'],
            ['name' => 'Create Suppliers', 'key' => 'suppliers.create'],
            ['name' => 'Update Suppliers', 'key' => 'suppliers.update'],
            ['name' => 'Delete Suppliers', 'key' => 'suppliers.delete'],

            // Purchase Orders
            ['name' => 'View Purchase Orders', 'key' => 'purchase_orders.view'],
            ['name' => 'Create Purchase Orders', 'key' => 'purchase_orders.create'],
            ['name' => 'Update Purchase Orders', 'key' => 'purchase_orders.update'],
            ['name' => 'Approve Purchase Orders', 'key' => 'purchase_orders.approve'],
            ['name' => 'Receive Purchase Orders', 'key' => 'purchase_orders.receive'],
            ['name' => 'Cancel Purchase Orders', 'key' => 'purchase_orders.cancel'],

            // Purchase Returns
            ['name' => 'View Purchase Returns', 'key' => 'purchase_returns.view'],
            ['name' => 'Create Purchase Returns', 'key' => 'purchase_returns.create'],
            ['name' => 'Approve Purchase Returns', 'key' => 'purchase_returns.approve'],
            ['name' => 'Dispatch Purchase Returns', 'key' => 'purchase_returns.dispatch'],
            ['name' => 'Cancel Purchase Returns', 'key' => 'purchase_returns.cancel'],

            // Customers
            ['name' => 'View Customers', 'key' => 'customers.view'],
            ['name' => 'Create Customers', 'key' => 'customers.create'],
            ['name' => 'Update Customers', 'key' => 'customers.update'],
            ['name' => 'Delete Customers', 'key' => 'customers.delete'],
            ['name' => 'Activate Customers', 'key' => 'customers.activate'],
            ['name' => 'Deactivate Customers', 'key' => 'customers.deactivate'],

            // Sales Orders
            ['name' => 'View Sales Orders', 'key' => 'sales_orders.view'],
            ['name' => 'Create Sales Orders', 'key' => 'sales_orders.create'],
            ['name' => 'Update Sales Orders', 'key' => 'sales_orders.update'],
            ['name' => 'Place Sales Orders', 'key' => 'sales_orders.place'],
            ['name' => 'Confirm Sales Orders', 'key' => 'sales_orders.confirm'],
            ['name' => 'Cancel Sales Orders', 'key' => 'sales_orders.cancel'],
            ['name' => 'Dispatch Sales Orders', 'key' => 'sales_orders.dispatch'],

            // Sales Returns
            ['name' => 'View Sales Returns', 'key' => 'sales_returns.view'],
            ['name' => 'Create Sales Returns', 'key' => 'sales_returns.create'],
            ['name' => 'Approve Sales Returns', 'key' => 'sales_returns.approve'],
            ['name' => 'Receive Sales Returns', 'key' => 'sales_returns.receive'],
            ['name' => 'Complete Sales Returns', 'key' => 'sales_returns.complete'],
            ['name' => 'Cancel Sales Returns', 'key' => 'sales_returns.cancel'],


            // Sales Invoices
            ['name' => 'View Sales Invoices', 'key' => 'sales_invoices.view'],
            ['name' => 'Create Sales Invoices', 'key' => 'sales_invoices.create'],
            ['name' => 'Issue Sales Invoices', 'key' => 'sales_invoices.issue'],
            ['name' => 'Cancel Sales Invoices', 'key' => 'sales_invoices.cancel'],

            // Sales Payments
            ['name' => 'View Sales Payments', 'key' => 'sales_payments.view'],
            ['name' => 'Create Sales Payments', 'key' => 'sales_payments.create'],
            ['name' => 'Cancel Sales Payments', 'key' => 'sales_payments.cancel'],

            // Reporting

            ['name' => 'View Sales Reports', 'key' => 'reports.sales.view'],
            ['name' => 'View Purchase Reports', 'key' => 'reports.purchases.view'],
            ['name' => 'View Inventory Reports', 'key' => 'reports.inventory.view'],
            ['name' => 'View Financial Reports', 'key' => 'reports.financial.view'],
            
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['key' => $permission['key']],
                [
                    'name' => $permission['name'],
                ]
            );
        }
    }
}
