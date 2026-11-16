<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamp('received_at');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'purchase_order_id', 'id']);

            // Company relationship
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            // Tenant-safe purchase order relationship
            $table->foreign(['company_id', 'purchase_order_id'])
                ->references(['company_id', 'id'])
                ->on('purchase_orders')
                ->restrictOnDelete();

            // Tenant-safe receiving user relationship
            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->index(['company_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipts');
    }
};
