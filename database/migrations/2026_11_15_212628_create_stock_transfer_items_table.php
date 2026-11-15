<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('stock_transfer_id');
            $table->foreignId('product_id');

            $table->decimal('quantity', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'stock_transfer_id',
                'product_id',
            ]);

            $table->foreign(['company_id', 'stock_transfer_id'])
                ->references(['company_id', 'id'])
                ->on('stock_transfers')
                ->cascadeOnDelete();

            $table->foreign(['company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('products');

            $table->index(['company_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
