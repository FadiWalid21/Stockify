<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('tax_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'email']);
            $table->unique(['company_id', 'tax_number']);
            $table->unique(['company_id', 'id']);

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};