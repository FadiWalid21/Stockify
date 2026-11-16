<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            $table->string('order_number');
            $table->string('status')->default('draft');

            $table->date('order_date');
            $table->date('expected_at')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->decimal('subtotal', 18, 4)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'order_number']);
            $table->unique(['company_id', 'id']);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign(['company_id', 'customer_id'])
                ->references(['company_id', 'id'])
                ->on('customers')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'confirmed_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'cancelled_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'order_date']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};