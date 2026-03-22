<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // basic, standard, premium
            $table->string('slug')->unique();
            $table->unsignedInteger('incident_limit_per_month')->nullable(); // null = unlimited
            $table->boolean('online_complaint_submission')->default(false);
            $table->boolean('mediation_scheduling')->default(false);
            $table->boolean('sms_status_updates')->default(false);
            $table->boolean('analytics_dashboard')->default(false);
            $table->boolean('auto_case_number')->default(false);
            $table->boolean('qr_verification')->default(false);
            $table->boolean('central_monitoring')->default(false);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
