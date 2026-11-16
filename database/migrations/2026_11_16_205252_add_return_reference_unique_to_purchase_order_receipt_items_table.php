<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_receipt_items', function (Blueprint $table) {
            $table->unique([
                'company_id',
                'purchase_order_id',
                'purchase_order_receipt_id',
                'id',
            ], 'po_receipt_items_return_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_receipt_items', function (Blueprint $table) {
            $table->dropUnique('po_receipt_items_return_reference_unique');
        });
    }
};
