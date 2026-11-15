<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreign(
                ['company_id', 'cancelled_by'],
                'stock_transfers_company_cancelled_by_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('users')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(
                'stock_transfers_company_cancelled_by_foreign'
            );

            $table->dropColumn([
                'cancelled_by',
                'cancelled_at',
            ]);
        });
    }
};
