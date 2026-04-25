<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('central')->table('central_activity_logs', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('central_activity_logs', 'tenant_user_id')) {
                $table->unsignedBigInteger('tenant_user_id')->nullable()->after('user_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('central_activity_logs', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('central_activity_logs', 'tenant_user_id')) {
                $table->dropColumn('tenant_user_id');
            }
        });
    }
};

