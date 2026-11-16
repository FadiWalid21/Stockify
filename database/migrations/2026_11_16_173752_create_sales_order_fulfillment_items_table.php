<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_fulfillment_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('sales_order_fulfillment_id');
            $table->unsignedBigInteger('sales_order_item_id');

            $table->decimal('quantity_dispatched', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'sales_order_fulfillment_id',
                'sales_order_item_id',
            ], 'so_fulfillment_items_unique');

            $table->foreign(
                ['company_id', 'sales_order_fulfillment_id', 'sales_order_id'],
                'so_fulfillment_items_fulfillment_fk'
            )
                ->references(['company_id', 'id', 'sales_order_id'])
                ->on('sales_order_fulfillments')
                ->restrictOnDelete();

            $table->foreign(
                ['company_id', 'sales_order_item_id', 'sales_order_id'],
                'so_fulfillment_items_order_item_fk'
            )
                ->references(['company_id', 'id', 'sales_order_id'])
                ->on('sales_order_items')
                ->restrictOnDelete();

            $table->index(['company_id', 'sales_order_id'], 'so_fulfillment_items_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_fulfillment_items');
    }
};