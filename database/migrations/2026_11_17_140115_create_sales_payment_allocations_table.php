<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_payment_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('customer_id');

            $table->decimal('amount', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'sales_payment_id',
                'sales_invoice_id',
            ]);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign([
                'company_id',
                'sales_payment_id',
                'customer_id',
            ])
                ->references([
                    'company_id',
                    'id',
                    'customer_id',
                ])
                ->on('sales_payments')
                ->restrictOnDelete();

            $table->foreign([
                'company_id',
                'sales_invoice_id',
                'customer_id',
            ])
                ->references([
                    'company_id',
                    'id',
                    'customer_id',
                ])
                ->on('sales_invoices')
                ->restrictOnDelete();

            $table->index([
                'company_id',
                'sales_invoice_id',
            ]);

            $table->index([
                'company_id',
                'sales_payment_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payment_allocations');
    }
};