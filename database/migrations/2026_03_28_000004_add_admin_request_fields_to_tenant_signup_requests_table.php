<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_signup_requests', function (Blueprint $table) {
            $table->string('requested_admin_name')->nullable()->after('contact_email');
            $table->string('requested_admin_email')->nullable()->after('requested_admin_name');
            $table->string('requested_admin_phone')->nullable()->after('requested_admin_email');
            $table->string('requested_admin_role')->nullable()->after('requested_admin_phone');
            $table->string('requested_admin_password_hash')->nullable()->after('requested_admin_role');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_signup_requests', function (Blueprint $table) {
            $table->dropColumn([
                'requested_admin_name',
                'requested_admin_email',
                'requested_admin_phone',
                'requested_admin_role',
                'requested_admin_password_hash',
            ]);
        });
    }
};
