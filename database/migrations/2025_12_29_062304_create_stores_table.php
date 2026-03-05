<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('slug');
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_sales')->default(true);
            $table->boolean('allow_inventory')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('type', ['central', 'branch', 'transit', 'virtual'])->default('branch');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('stores');
    }
};