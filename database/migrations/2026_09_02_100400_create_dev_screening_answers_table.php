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
        // Resposta do dev a uma pergunta do questionário. Nas dissertativas o conteúdo
        // fica em `response`; nas de escolha, nas opções marcadas em
        // `dev_screening_answer_options`
        Schema::create('dev_screening_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dev_screening_questionnaire_id')->constrained('dev_screening_questionnaires')->cascadeOnDelete();
            $table->foreignUuid('screening_question_id')->constrained('screening_questions')->cascadeOnDelete();
            $table->text('response')->nullable();
            $table->timestamps();

            // Uma única resposta por pergunta em cada preenchimento
            $table->unique(
                ['dev_screening_questionnaire_id', 'screening_question_id'],
                'dev_screening_answers_questionnaire_question_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_screening_answers');
    }
};
