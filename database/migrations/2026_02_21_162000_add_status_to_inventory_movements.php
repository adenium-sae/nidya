<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->after('type');
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->after('type');
        });

        // stock_transfers already has a status column, but we might want to ensure it has 'pending', 'completed', 'cancelled'
        // According to previous migration it has: ['pending', 'in_transit', 'completed', 'cancelled']
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
