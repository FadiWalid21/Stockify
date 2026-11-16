<?php

use App\Domains\Purchase\Enums\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            $table->string('order_number', 50);
            $table->string('status', 30)
                ->default(PurchaseOrderStatus::DRAFT->value);

            $table->date('order_date');
            $table->date('expected_at')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraints
            $table->unique(['company_id', 'order_number']);
            $table->unique(['company_id', 'id']);

            // Company relationship
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            // Tenant-safe supplier relationship
            $table->foreign(['company_id', 'supplier_id'])
                ->references(['company_id', 'id'])
                ->on('suppliers')
                ->restrictOnDelete();

            // Tenant-safe warehouse relationship
            $table->foreign(['company_id', 'warehouse_id'])
                ->references(['company_id', 'id'])
                ->on('warehouses')
                ->restrictOnDelete();

            // Tenant-safe user relationships
            $table->foreign(['company_id', 'user_id'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'approved_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['company_id', 'cancelled_by'])
                ->references(['company_id', 'id'])
                ->on('users')
                ->restrictOnDelete();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'order_date']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
