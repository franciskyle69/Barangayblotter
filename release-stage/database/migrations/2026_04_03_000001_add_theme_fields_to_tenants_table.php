<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('theme_preset')->default('default')->after('sidebar_label');
            $table->string('theme_primary_color', 7)->default('#635bff')->after('theme_preset');
            $table->string('theme_bg_color', 7)->default('#f8fafc')->after('theme_primary_color');
            $table->string('theme_sidebar_color', 7)->default('#121621')->after('theme_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'theme_preset',
                'theme_primary_color',
                'theme_bg_color',
                'theme_sidebar_color',
            ]);
        });
    }
};