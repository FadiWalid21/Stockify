<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();

            $table->string('return_number');
            $table->string('status')->default('draft');

            $table->date('return_date');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->decimal('subtotal', 18, 4)->default(0);

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'return_number'],
                'purchase_returns_company_number_unique'
            );

            $table->unique(
                ['company_id', 'id'],
                'purchase_returns_company_id_unique'
            );

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign(
                ['company_id', 'supplier_id']
            )->references(
                ['company_id', 'id']
            )->on('suppliers')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'purchase_order_id']
            )->references(
                ['company_id', 'id']
            )->on('purchase_orders')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'warehouse_id']
            )->references(
                ['company_id', 'id']
            )->on('warehouses')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'user_id']
            )->references(
                ['company_id', 'id']
            )->on('users')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'approved_by']
            )->references(
                ['company_id', 'id']
            )->on('users')->restrictOnDelete();

            $table->foreign(
                ['company_id', 'dispatched_by']
            )->references(
                ['company_id', 'id']
            )->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
