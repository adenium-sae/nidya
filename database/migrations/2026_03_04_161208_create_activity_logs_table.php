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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // auth, inventory, sales, catalog, organization, system
            $table->string('event'); // login, product.created, stock.adjusted, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('level')->default('info'); // info, warning, error, critical
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index(['level', 'created_at']);
            $table->index('user_id');
            $table->index('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
