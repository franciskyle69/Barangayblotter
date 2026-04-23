<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Support tickets live in the CENTRAL database (not inside each tenant
     * DB) because:
     *   1. The super-admin dashboard must see tickets across every barangay
     *      in a single query.
     *   2. If a tenant's DB is corrupted/unreachable, they still need a way
     *      to reach the central team — so the support table must be
     *      independent of any tenant connection.
     *   3. `tenant_id` lives on the ticket so we can filter per-barangay.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('subject');
            // category + priority stored as short strings (not enum) so we
            // can extend the vocabulary without a schema migration.
            $table->string('category', 32)->default('other');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 32)->default('open')->index();

            // Opener identity is stored as a SNAPSHOT because tenant users
            // live in the tenant database, not central. Saving the raw id
            // without a FK lets us show "who filed it" even after the user
            // is removed from their tenant.
            $table->unsignedBigInteger('opened_by_user_id')->nullable();
            $table->string('opened_by_name')->nullable();
            $table->string('opened_by_email')->nullable();

            // Closure tracked against a central (super-admin) user, so a
            // real FK is safe here.
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('closure_note')->nullable();

            // Used for "most recently updated first" sorting in both the
            // tenant inbox and the super-admin queue.
            $table->timestamp('last_activity_at')->nullable()->index();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
