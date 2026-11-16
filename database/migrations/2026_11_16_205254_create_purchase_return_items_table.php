<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_return_id');

            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();

            $table->unsignedBigInteger('purchase_order_receipt_id')->nullable();
            $table->unsignedBigInteger('purchase_order_receipt_item_id')->nullable();

            $table->unsignedBigInteger('product_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('line_total', 18, 4);

            $table->timestamps();

            $table->unique(
                ['company_id', 'purchase_return_id', 'id'],
                'purchase_return_items_return_id_unique'
            );

            $table->foreign(
                ['company_id', 'purchase_return_id']
            )->references(
                ['company_id', 'id']
            )->on('purchase_returns')->cascadeOnDelete();

            $table->foreign(
                ['company_id', 'purchase_order_id']
            )->references(
                ['company_id', 'id']
            )->on('purchase_orders')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'purchase_order_item_id', 'purchase_order_id']
            )->references(
                ['company_id', 'id', 'purchase_order_id']
            )->on('purchase_order_items')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'purchase_order_receipt_id', 'purchase_order_id']
            )->references(
                ['company_id', 'id', 'purchase_order_id']
            )->on('purchase_order_receipts')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'purchase_order_receipt_item_id', 'purchase_order_receipt_id']
            )->references(
                ['company_id', 'id', 'purchase_order_receipt_id']
            )->on('purchase_order_receipt_items')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'product_id']
            )->references(
                ['company_id', 'id']
            )->on('products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
