<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('patrol_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id'); // community watch who logged
            $table->date('patrol_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('area_patrolled')->nullable();
            $table->text('activities')->nullable();
            $table->text('incidents_observed')->nullable();
            $table->text('response_details')->nullable();
            $table->unsignedInteger('response_time_minutes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'patrol_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_logs');
    }
};
