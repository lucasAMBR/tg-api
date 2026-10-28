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
        // Opções marcadas pelo dev nas perguntas de escolha única (uma linha) e de
        // múltipla escolha (uma linha por opção marcada)
        Schema::create('dev_screening_answer_options', function (Blueprint $table) {
            // Tabela pivô pura: a chave primária é o par resposta x opção, sem `id`
            // próprio, então o attach() do belongsToMany funciona sem model de pivô
            $table->uuid('dev_screening_answer_id');
            $table->uuid('screening_question_option_id');
            $table->timestamps();

            // Chaves e índices nomeados à mão: os nomes gerados por convenção passariam
            // do limite de 63 caracteres de identificador do Postgres
            $table->foreign('dev_screening_answer_id', 'dev_screening_answer_options_answer_id_foreign')
                ->references('id')->on('dev_screening_answers')->cascadeOnDelete();

            $table->foreign('screening_question_option_id', 'dev_screening_answer_options_option_id_foreign')
                ->references('id')->on('screening_question_options')->cascadeOnDelete();

            // Uma opção marcada uma única vez em cada resposta
            $table->primary(
                ['dev_screening_answer_id', 'screening_question_option_id'],
                'dev_screening_answer_options_primary'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_screening_answer_options');
    }
};
