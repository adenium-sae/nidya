<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('display_name');
            $table->string('icon_url')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'icon_url']);
        });
    }
};
