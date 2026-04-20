<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('blotter_number')->nullable(); // auto-generated for premium, manual for others
            $table->string('incident_type'); // complaint type / category
            $table->text('description');
            $table->string('location')->nullable();
            $table->dateTime('incident_date');
            $table->string('complainant_name');
            $table->string('complainant_contact')->nullable();
            $table->string('complainant_address')->nullable();
            $table->foreignId('complainant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('respondent_name');
            $table->string('respondent_contact')->nullable();
            $table->string('respondent_address')->nullable();
            $table->string('status')->default('open'); // open, under_mediation, settled, escalated_to_barangay
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('submitted_online')->default(false);
            $table->timestamps();
            $table->index(['tenant_id', 'incident_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
