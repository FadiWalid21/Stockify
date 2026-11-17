<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('warehouse_id');

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();

            $table->string('return_number', 50);
            $table->string('status', 30)->default('draft');

            $table->date('return_date');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->decimal('subtotal', 18, 4)->default(0);

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Tenant-aware unique constraints
            $table->unique(
                ['company_id', 'id'],
                'sales_returns_company_id_id_unique'
            );

            $table->unique(
                ['company_id', 'id', 'sales_order_id'],
                'sales_returns_order_reference_unique'
            );

            $table->unique(
                ['company_id', 'return_number'],
                'sales_returns_company_number_unique'
            );

            // Customer relationship
            $table->foreign(['company_id', 'customer_id'])
                ->references(['company_id', 'id'])
                ->on('customers')
                ->restrictOnDelete();

            // Sales order relationship
            $table->foreign([
                'company_id',
                'customer_id',
                'sales_order_id',
            ], 'sales_returns_customer_order_fk')
                ->references([
                    'company_id',
                    'customer_id',
                    'id',
                ])
                ->on('sales_orders')
                ->restrictOnDelete();

            // Warehouse relationship
            $table->foreign(['company_id', 'warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            // Created by
            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            // Approved by
            $table->foreign(['company_id', 'approved_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            // Received by
            $table->foreign(['company_id', 'received_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->index(
                ['company_id', 'status', 'return_date'],
                'sales_returns_company_status_date_index'
            );

            $table->index(
                ['company_id', 'sales_order_id'],
                'sales_returns_company_order_index'
            );

            $table->index(
                ['company_id', 'customer_id'],
                'sales_returns_company_customer_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
