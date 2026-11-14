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
    Schema::create('products', function (Blueprint $table) {
        $table->id();

        $table->foreignId('company_id')
            ->constrained('companies')
            ->restrictOnDelete();

        $table->foreignId('unit_id')
            ->constrained('units')
            ->restrictOnDelete();

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('categories')
            ->nullOnDelete();

        $table->string('name');
        $table->string('sku');
        $table->text('description')->nullable();

        // We'll add unit_id when we create the global units table.

        $table->timestamps();

        $table->unique(['company_id', 'sku']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
