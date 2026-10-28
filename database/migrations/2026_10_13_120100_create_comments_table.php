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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Usuário é excluído de verdade: o comentário fica sem autor para não
            // levar junto, pelo cascade do parent_id, as respostas de outras pessoas.
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->text('content');
            // Conteúdo original preservado quando o comentário é removido.
            $table->text('original_content')->nullable();
            // Remoção lógica: a linha permanece para não sumir com as respostas.
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            // A árvore inteira é buscada por parent_id (CTE recursiva).
            $table->index('parent_id');
        });

        // FK auto-referenciada só pode ser criada depois que a tabela existe.
        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
