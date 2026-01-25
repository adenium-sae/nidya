<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code')->index();
            $table->string('name');
            $table->enum('type', ['shelf', 'box', 'pallet', 'display', 'floor', 'other'])->default('shelf');
            $table->string('aisle')->nullable();
            $table->string('section')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'warehouse_id', 'code']);
        });

        Schema::create('stock', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('available')->virtualAs('quantity - reserved');
            $table->decimal('avg_cost', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id', 'storage_location_id'], 'stock_location_unique');
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('storage_location_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['entry', 'exit', 'transfer', 'adjustment', 'sale', 'return', 'damage', 'production']);
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('related_movement_id')->nullable();
            $table->nullableUuidMorphs('movable');
            $table->timestamps();
            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['tenant_id', 'warehouse_id', 'created_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('related_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('folio')->unique();
            $table->foreignUuid('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', ['pending', 'in_transit', 'completed', 'cancelled'])->default('pending');
            $table->foreignUuid('requested_by')->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->foreignUuid('received_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_requested');
            $table->integer('quantity_sent')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('folio')->unique();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['increase', 'decrease', 'recount'])->default('recount');
            $table->enum('reason', ['damaged', 'lost', 'found', 'expired', 'recount', 'other']);
            $table->foreignUuid('user_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('difference')->virtualAs('quantity_after - quantity_before');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('storage_locations');
    }
};