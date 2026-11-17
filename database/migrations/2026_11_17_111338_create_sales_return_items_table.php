<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_return_id');

            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('sales_order_item_id');

            $table->unsignedBigInteger('sales_order_fulfillment_id');
            $table->unsignedBigInteger('sales_order_fulfillment_item_id');

            $table->unsignedBigInteger('product_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('line_total', 18, 4);

            $table->timestamps();

            $table->unique(
                ['company_id', 'sales_return_id', 'id'],
                'sales_return_items_return_id_unique'
            );

            // Sales return relationship
            $table->foreign([
                'company_id',
                'sales_return_id',
                'sales_order_id',
            ], 'sales_return_items_return_order_fk')
                ->references([
                    'company_id',
                    'id',
                    'sales_order_id',
                ])
                ->on('sales_returns')
                ->cascadeOnDelete();

            // Sales order relationship
            $table->foreign(['company_id', 'sales_order_id'])
                ->references(['company_id', 'id'])
                ->on('sales_orders')
                ->restrictOnDelete();

            // Sales order item must belong to this exact order
            $table->foreign([
                'company_id',
                'sales_order_id',
                'sales_order_item_id',
            ], 'sales_return_items_order_item_fk')
                ->references([
                    'company_id',
                    'sales_order_id',
                    'id',
                ])
                ->on('sales_order_items')
                ->restrictOnDelete();

            // Fulfillment must belong to this exact order
            $table->foreign([
                'company_id',
                'sales_order_id',
                'sales_order_fulfillment_id',
            ], 'sales_return_items_fulfillment_fk')
                ->references([
                    'company_id',
                    'sales_order_id',
                    'id',
                ])
                ->on('sales_order_fulfillments')
                ->restrictOnDelete();

            // Fulfillment item must belong to this exact fulfillment and order
            $table->foreign([
                'company_id',
                'sales_order_id',
                'sales_order_fulfillment_id',
                'sales_order_fulfillment_item_id',
            ], 'sales_return_items_fulfillment_item_fk')
                ->references([
                    'company_id',
                    'sales_order_id',
                    'sales_order_fulfillment_id',
                    'id',
                ])
                ->on('sales_order_fulfillment_items')
                ->restrictOnDelete();

            // Product relationship
            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->restrictOnDelete();

            $table->index(
                ['company_id', 'sales_order_fulfillment_item_id'],
                'sales_return_items_fulfillment_item_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
