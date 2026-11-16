<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);

            $table->timestamps();

            // Prevent duplicate products in the same purchase order
            $table->unique([
                'company_id',
                'purchase_order_id',
                'product_id',
            ]);

            $table->unique([
                'company_id',
                'purchase_order_id',
                'id',
            ]);
            // Company relationship
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            // Tenant-safe purchase order relationship
            $table->foreign(['company_id', 'purchase_order_id'])
                ->references(['company_id', 'id'])
                ->on('purchase_orders')
                ->cascadeOnDelete();

            // Tenant-safe product relationship
            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products')
                ->restrictOnDelete();

            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
