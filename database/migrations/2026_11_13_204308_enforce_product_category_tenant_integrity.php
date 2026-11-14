<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'id'],
                'categories_company_id_id_unique'
            );
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);

            $table->foreign(
                ['company_id', 'category_id'],
                'products_company_category_foreign'
            )
                ->references(['company_id', 'id'])
                ->on('categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_company_category_foreign');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_company_id_id_unique');
        });
    }
};