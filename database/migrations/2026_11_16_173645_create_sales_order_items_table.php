<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('product_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('line_total', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'sales_order_id',
                'product_id',
            ]);

            $table->unique([
                'company_id',
                'sales_order_id',
                'id',
            ]);

            $table->unique([
                'company_id',
                'id',
                'sales_order_id',
            ]);
            

            $table->foreign(['company_id', 'sales_order_id'])
                ->references(['company_id', 'id'])
                ->on('sales_orders')
                ->cascadeOnDelete();

            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->restrictOnDelete();

            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};