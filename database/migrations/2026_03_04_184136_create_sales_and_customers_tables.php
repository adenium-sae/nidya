<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('tax_id')->nullable()->index();
            $table->foreignUuid('address_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('folio')->unique();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('completed'); // pending, completed, cancelled
            $table->string('payment_method')->nullable();
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
