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
        // Alternativas marcadas pelo dev nas perguntas de escolha única (uma linha) e
        // de múltipla escolha (uma linha por alternativa marcada)
        Schema::create('dev_language_assessment_answer_options', function (Blueprint $table) {
            // Tabela pivô pura: a chave primária é o par resposta x alternativa, sem
            // `id` próprio, então o attach() do belongsToMany funciona sem model de pivô
            $table->uuid('dev_language_assessment_answer_id');
            $table->uuid('language_assessment_question_option_id');
            $table->timestamps();

            // Chaves e índices nomeados à mão: os nomes gerados por convenção passariam
            // do limite de 63 caracteres de identificador do Postgres
            $table->foreign('dev_language_assessment_answer_id', 'dev_language_answer_options_answer_id_foreign')
                ->references('id')->on('dev_language_assessment_answers')->cascadeOnDelete();

            $table->foreign('language_assessment_question_option_id', 'dev_language_answer_options_option_id_foreign')
                ->references('id')->on('language_assessment_question_options')->cascadeOnDelete();

            // Uma alternativa marcada uma única vez em cada resposta
            $table->primary(
                ['dev_language_assessment_answer_id', 'language_assessment_question_option_id'],
                'dev_language_assessment_answer_options_primary'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_language_assessment_answer_options');
    }
};
