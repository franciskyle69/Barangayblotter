<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('login_background_path')->nullable()->after('sidebar_label');
            $table->decimal('login_background_opacity', 4, 2)->default(0.45)->after('login_background_path');
            $table->unsignedSmallInteger('login_background_blur')->default(0)->after('login_background_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'login_background_path',
                'login_background_opacity',
                'login_background_blur',
            ]);
        });
    }
};
