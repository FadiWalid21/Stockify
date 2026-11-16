<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_fulfillments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('user_id');

            $table->timestamp('dispatched_at');

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique([
                'company_id',
                'sales_order_id',
                'id',
            ]);

            $table->unique([
                'company_id',
                'id',
                'sales_order_id',
            ]);

            $table->foreign(['company_id', 'sales_order_id'])
                ->references(['company_id', 'id'])
                ->on('sales_orders')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->index(['company_id', 'dispatched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_fulfillments');
    }
};