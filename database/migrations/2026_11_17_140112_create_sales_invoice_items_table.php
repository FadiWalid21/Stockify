<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('sales_order_item_id');
            $table->unsignedBigInteger('product_id');

            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);

            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);

            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);

            $table->decimal('line_subtotal', 18, 4);
            $table->decimal('line_total', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'sales_invoice_id',
                'id',
            ]);

            $table->foreign([
                'company_id',
                'sales_invoice_id',
                'sales_order_id',
            ])
                ->references([
                    'company_id',
                    'id',
                    'sales_order_id',
                ])
                ->on('sales_invoices')
                ->cascadeOnDelete();

            $table->foreign([
                'company_id',
                'sales_order_id',
                'sales_order_item_id',
            ])
                ->references([
                    'company_id',
                    'sales_order_id',
                    'id',
                ])
                ->on('sales_order_items')
                ->restrictOnDelete();

            $table->foreign([
                'company_id',
                'product_id',
            ])
                ->references([
                    'company_id',
                    'id',
                ])
                ->on('products')
                ->restrictOnDelete();

            $table->index([
                'company_id',
                'sales_order_item_id',
            ]);

            $table->index([
                'company_id',
                'product_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
