<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tool_name', 100);
            $table->json('tool_input');
            $table->json('tool_output')->nullable();
            $table->json('rollback_data')->nullable(); // State before the action (for undo)
            $table->enum('status', ['pending', 'success', 'failed', 'rolled_back'])->default('pending');
            $table->timestamp('created_at')->nullable();

            $table->index('conversation_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_actions');
    }
};
