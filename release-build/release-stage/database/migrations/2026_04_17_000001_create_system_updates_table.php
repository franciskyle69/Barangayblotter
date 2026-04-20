<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = (string) config('tenancy.central_connection', 'central');

        Schema::connection($connection)->create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version')->nullable();
            $table->string('status');
            $table->longText('log')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        $connection = (string) config('tenancy.central_connection', 'central');

        Schema::connection($connection)->dropIfExists('system_updates');
    }
};

