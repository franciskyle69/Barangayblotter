<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $central = config('tenancy.central_connection', 'central');

        Schema::connection($central)->create('tenant_updates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable()->index();
            $table->string('status', 32)->index();
            $table->longText('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $central = config('tenancy.central_connection', 'central');
        Schema::connection($central)->dropIfExists('tenant_updates');
    }
};

