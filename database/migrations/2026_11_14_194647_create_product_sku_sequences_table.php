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
        Schema::create('product_sku_sequences', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->primary()
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedBigInteger('next_number')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sku_sequences');
    }
};
