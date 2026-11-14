<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'id'],
                'users_company_id_id_unique'
            );
        });

        Schema::table('inventory_operations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->foreign(
                ['company_id', 'user_id'],
                'inventory_operations_company_user_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_operations', function (Blueprint $table) {
            $table->dropForeign(
                'inventory_operations_company_user_foreign'
            );

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_company_id_id_unique');
        });
    }
};