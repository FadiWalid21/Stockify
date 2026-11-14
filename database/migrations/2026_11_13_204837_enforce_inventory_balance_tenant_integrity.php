<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'id'],
                'products_company_id_id_unique'
            );
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'id'],
                'warehouses_company_id_id_unique'
            );
        });

        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['warehouse_id']);

            $table->foreign(
                ['company_id', 'product_id'],
                'inventory_balances_company_product_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('products')
                ->restrictOnDelete();

            $table->foreign(
                ['company_id', 'warehouse_id'],
                'inventory_balances_company_warehouse_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropForeign(
                'inventory_balances_company_product_foreign'
            );

            $table->dropForeign(
                'inventory_balances_company_warehouse_foreign'
            );

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_id_id_unique');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_company_id_id_unique');
        });
    }
};