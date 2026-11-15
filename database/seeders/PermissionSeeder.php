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