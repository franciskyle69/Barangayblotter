<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blotter_requests', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable()->after('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('blotter_requests', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->dropColumn(['admin_user_id', 'remarks']);
        });
    }
};
