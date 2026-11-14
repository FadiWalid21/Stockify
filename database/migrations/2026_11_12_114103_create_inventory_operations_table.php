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
    Schema::create('inventory_operations', function (Blueprint $table) {
        $table->id();

        $table->foreignId('company_id')
            ->constrained('companies')
            ->restrictOnDelete();

        $table->foreignId('user_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->string('type'); // STOCK_IN, STOCK_OUT, OPENING_BALANCE, ADJUSTMENT

        $table->string('reference')->nullable();
        $table->text('notes')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_operations');
    }
};
