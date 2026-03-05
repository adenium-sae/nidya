<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('secondary_color')->default('#6B7280')->after('primary_color');
            $table->string('accent_color')->default('#F59E0B')->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'secondary_color', 'accent_color']);
        });
    }
};
