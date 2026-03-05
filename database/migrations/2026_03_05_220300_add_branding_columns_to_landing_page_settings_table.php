<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('id');
            $table->string('primary_color')->default('#171717')->after('contact_phone');
            $table->string('secondary_color')->default('#F5F5F5')->after('primary_color');
            $table->string('accent_color')->default('#F5F5F5')->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'primary_color', 'secondary_color', 'accent_color']);
        });
    }
};
