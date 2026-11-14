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
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();

        $table->foreignId('inventory_operation_id')
            ->constrained('inventory_operations')
            ->restrictOnDelete();

        $table->foreignId('company_id')
            ->constrained('companies')
            ->restrictOnDelete();

        $table->foreignId('product_id')
            ->constrained('products')
            ->restrictOnDelete();

        $table->foreignId('warehouse_id')
            ->constrained('warehouses')
            ->restrictOnDelete();

        $table->decimal('quantity', 18, 4);
        $table->string('type')->in; // IN, OUT, ADJUSTMENT

        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index(['company_id', 'product_id', 'warehouse_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
