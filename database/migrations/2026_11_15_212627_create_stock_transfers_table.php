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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('from_warehouse_id');
            $table->foreignId('to_warehouse_id');

            $table->foreignId('created_by');

            $table->string('status')->default('draft');

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('received_by')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'id']);

            $table->foreign(['company_id', 'from_warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses');

            $table->foreign(['company_id', 'to_warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses');

            $table->foreign(['company_id', 'created_by'])
                ->references(['company_id', 'id'])
                ->on('users');

            $table->foreign(['company_id', 'approved_by'])
                ->references(['company_id', 'id'])
                ->on('users');

            $table->foreign(['company_id', 'received_by'])
                ->references(['company_id', 'id'])
                ->on('users');

            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
