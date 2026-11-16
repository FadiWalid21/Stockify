<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipt_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('purchase_order_receipt_id');
            $table->unsignedBigInteger('purchase_order_item_id');

            $table->decimal('quantity_received', 18, 4);

            $table->timestamps();

            $table->unique([
                'company_id',
                'purchase_order_receipt_id',
                'purchase_order_item_id',
            ]);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            // Receipt must belong to this exact purchase order
            $table->foreign([
                'company_id',
                'purchase_order_id',
                'purchase_order_receipt_id',
            ], 'po_receipt_items_receipt_fk')
                ->references([
                    'company_id',
                    'purchase_order_id',
                    'id',
                ])
                ->on('purchase_order_receipts')
                ->cascadeOnDelete();

            // Item must belong to this exact purchase order
            $table->foreign([
            'company_id',
            'purchase_order_id',
            'purchase_order_item_id',
        ], 'po_receipt_items_order_item_fk')
            ->references([
                'company_id',
                'purchase_order_id',
                'id',
            ])
            ->on('purchase_order_items')
            ->restrictOnDelete();

            $table->index([
                'company_id',
                'purchase_order_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipt_items');
    }
};
