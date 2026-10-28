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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            // Quando o destinatário leu. É preenchido mesmo com a confirmação de leitura
            // desligada, porque é a base da contagem de não lidas do destinatário
            $table->timestamp('read_at')->nullable();
            // Marcado quando o destinatário leu com a confirmação de leitura desligada:
            // o remetente não fica sabendo dessa leitura
            $table->boolean('read_receipt_hidden')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
