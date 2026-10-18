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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Os dois participantes, sempre gravados em ordem (user_one_id < user_two_id) para o
            // índice único garantir uma só conversa por par de usuários
            $table->foreignUuid('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_two_id')->constrained('users')->cascadeOnDelete();
            // Ordena a lista de conversas. Fica nulo enquanto a conversa não tiver mensagens
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index('user_two_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
