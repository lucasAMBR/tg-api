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
        // Resposta do dev a uma pergunta da prova, já com o resultado da correção.
        // Nas perguntas escritas o conteúdo fica em `response`; nas de escolha, nas
        // alternativas marcadas em `dev_language_assessment_answer_options`
        Schema::create('dev_language_assessment_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dev_language_assessment_id')->constrained('dev_language_assessments')->cascadeOnDelete();
            $table->foreignUuid('language_assessment_question_id')->constrained('language_assessment_questions')->cascadeOnDelete();
            $table->text('response')->nullable();
            // Correção automática, feita na entrega
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('earned_score')->default(0);
            $table->timestamps();

            // Uma única resposta por pergunta em cada prova
            $table->unique(
                ['dev_language_assessment_id', 'language_assessment_question_id'],
                'dev_language_assessment_answers_assessment_question_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_language_assessment_answers');
    }
};
