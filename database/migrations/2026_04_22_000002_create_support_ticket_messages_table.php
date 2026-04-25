<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Threaded messages attached to a support ticket. Author identity is
     * stored as a snapshot so we keep the conversation history intact even
     * if the central/tenant user is later deleted. `author_scope` tells us
     * which side of the conversation the message came from.
     */
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            // 'tenant' | 'super_admin' — enforced at the application layer.
            $table->string('author_scope', 16);
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();

            $table->text('body');

            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
