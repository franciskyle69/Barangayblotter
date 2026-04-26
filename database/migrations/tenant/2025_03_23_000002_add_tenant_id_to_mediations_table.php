<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::table('mediations', function (Blueprint $table) {
            if (!Schema::hasColumn('mediations', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            }
        });

        // Backfill tenant_id from parent incident
        DB::statement('
            UPDATE mediations
            SET tenant_id = (
                SELECT incidents.tenant_id
                FROM incidents
                WHERE incidents.id = mediations.incident_id
            )
            WHERE tenant_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('mediations', function (Blueprint $table) {
            if (Schema::hasColumn('mediations', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
