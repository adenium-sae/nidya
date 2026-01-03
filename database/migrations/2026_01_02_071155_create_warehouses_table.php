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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->enum("type", ["central", "branch", "other"])->default("branch");
            $table->boolean("is_active")->default(true);
            $table->foreignUuid("branch_id")->nullable()->constrained("branches")->cascadeOnDelete();
            $table->foreignUuid("store_id")->nullable()->constrained("stores")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
