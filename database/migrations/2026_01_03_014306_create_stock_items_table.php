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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("product_id")->constrained("products")->cascadeOnDelete();
            $table->foreignUuid("storage_item_id")->constrained("storage_items")->cascadeOnDelete();
            $table->integer("stock")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
