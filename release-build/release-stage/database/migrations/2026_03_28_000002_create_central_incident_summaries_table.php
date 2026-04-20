<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_incident_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('tenant_name');
            $table->string('tenant_slug')->nullable();
            $table->unsignedBigInteger('tenant_incident_id');
            $table->string('blotter_number')->nullable();
            $table->string('incident_type');
            $table->string('status')->index();
            $table->dateTime('incident_date')->nullable();
            $table->unsignedBigInteger('reported_by_user_id')->nullable();
            $table->string('reported_by_name')->nullable();
            $table->dateTime('created_at_in_tenant')->nullable();
            $table->dateTime('updated_at_in_tenant')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'tenant_incident_id'], 'central_incident_tenant_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at_in_tenant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_incident_summaries');
    }
};
