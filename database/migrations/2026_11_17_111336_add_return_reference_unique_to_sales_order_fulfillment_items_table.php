<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_fulfillment_items', function (Blueprint $table) {
            $table->unique([
                'company_id',
                'sales_order_id',
                'sales_order_fulfillment_id',
                'id',
            ], 'so_fulfillment_items_return_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_fulfillment_items', function (Blueprint $table) {
            $table->dropUnique(
                'so_fulfillment_items_return_reference_unique'
            );
        });
    }
};
