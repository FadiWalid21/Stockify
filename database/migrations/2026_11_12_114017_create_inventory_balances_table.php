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
    Schema::create('inventory_balances', function (Blueprint $table) {
        $table->id();

        $table->foreignId('company_id')
            ->constrained('companies')
            ->restrictOnDelete();

        $table->foreignId('product_id')
            ->constrained('products')
            ->restrictOnDelete();

        $table->foreignId('warehouse_id')
            ->constrained('warehouses')
            ->restrictOnDelete();

        $table->decimal('quantity', 18, 4)->default(0);

        $table->timestamps();

        $table->unique(['company_id', 'product_id', 'warehouse_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
