<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('blotter_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->unsignedBigInteger('requested_by_user_id');
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending'); // pending, approved, printed, rejected
            $table->string('certificate_path')->nullable();
            $table->string('verification_code')->nullable(); // for QR verification (premium)
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blotter_requests');
    }
};
