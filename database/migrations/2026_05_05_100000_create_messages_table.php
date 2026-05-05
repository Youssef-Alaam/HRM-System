<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('recipient_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('parent_message_id')
                ->nullable()
                ->constrained('messages')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['org_id', 'recipient_id', 'read_at']);
            $table->index(['org_id', 'sender_id']);
            $table->index('parent_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
