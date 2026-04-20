<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subdomain')->nullable()->unique()->after('slug');
            $table->string('custom_domain')->nullable()->unique()->after('subdomain');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['subdomain']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn(['subdomain', 'custom_domain']);
        });
    }
};
