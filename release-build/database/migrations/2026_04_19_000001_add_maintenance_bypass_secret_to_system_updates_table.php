<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = (string) config('tenancy.central_connection', 'central');

        Schema::connection($connection)->table('system_updates', function (Blueprint $table) {
            $table->string('maintenance_bypass_secret', 64)->nullable()->after('log');
        });
    }

    public function down(): void
    {
        $connection = (string) config('tenancy.central_connection', 'central');

        Schema::connection($connection)->table('system_updates', function (Blueprint $table) {
            $table->dropColumn('maintenance_bypass_secret');
        });
    }
};
