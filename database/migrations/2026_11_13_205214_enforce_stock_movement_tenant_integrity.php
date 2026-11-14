<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['warehouse_id']);

            $table->foreign(
                ['company_id', 'product_id'],
                'stock_movements_company_product_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('products')
                ->restrictOnDelete();

            $table->foreign(
                ['company_id', 'warehouse_id'],
                'stock_movements_company_warehouse_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(
                'stock_movements_company_product_foreign'
            );

            $table->dropForeign(
                'stock_movements_company_warehouse_foreign'
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
    }
};