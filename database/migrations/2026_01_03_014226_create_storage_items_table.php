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
        Schema::create('storage_items', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("label");
            $table->enum("batch_type", ["bag", "box", "stand", "in_sale", "other"]);
            $table->foreignUuid("warehouse_id")->constrained("warehouses")->cascadeOnDelete();
            $table->foreignUuid("product_id")->constrained("products")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_items');
    }
};
