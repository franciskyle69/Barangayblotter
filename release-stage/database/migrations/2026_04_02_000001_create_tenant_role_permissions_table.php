<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config('tenancy.central_connection', 'central');

        if (Schema::connection($connection)->hasTable('tenant_role_permissions')) {
            return;
        }

        Schema::connection($connection)->create('tenant_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role_name');
            $table->string('permission_name');
            $table->timestamps();

            $table->unique(['tenant_id', 'role_name', 'permission_name'], 'tenant_role_permissions_unique');
            $table->index(['tenant_id', 'role_name'], 'tenant_role_permissions_lookup');
        });
    }

    public function down(): void
    {
        $connection = config('tenancy.central_connection', 'central');

        Schema::connection($connection)->dropIfExists('tenant_role_permissions');
    }
};
