<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cancelled_by')->nullable();

            $table->string('invoice_number');
            $table->string('status')->default('draft');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->unique(['company_id', 'id']);

            $table->unique([
                'company_id',
                'id',
                'customer_id',
            ]);

            // Required by sales_invoice_items composite foreign key.
            $table->unique([
                'company_id',
                'id',
                'sales_order_id',
            ]);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign(['company_id', 'customer_id'])
                ->references(['company_id', 'id'])
                ->on('customers')
                ->restrictOnDelete();

            $table->foreign([
                'company_id',
                'customer_id',
                'sales_order_id',
            ])
                ->references([
                    'company_id',
                    'customer_id',
                    'id',
                ])
                ->on('sales_orders')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'cancelled_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_date']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'sales_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
