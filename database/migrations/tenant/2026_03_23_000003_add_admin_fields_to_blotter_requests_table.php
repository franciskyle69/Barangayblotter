<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::table('blotter_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('blotter_requests', 'admin_user_id')) {
                $table->unsignedBigInteger('admin_user_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('blotter_requests', 'remarks')) {
                $table->text('remarks')->nullable()->after('admin_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blotter_requests', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('blotter_requests', 'admin_user_id')) {
                $drops[] = 'admin_user_id';
            }
            if (Schema::hasColumn('blotter_requests', 'remarks')) {
                $drops[] = 'remarks';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
