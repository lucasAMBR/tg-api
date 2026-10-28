<?php

use App\Enums\TranslationStatusEnum;
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
        Schema::create('screening_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('screening_questionnaire_id')->constrained('screening_questionnaires')->cascadeOnDelete();
            $table->text('question');
            $table->text('question_pt')->nullable();
            $table->text('question_en')->nullable();
            $table->string('translation_status')->default(TranslationStatusEnum::PENDING->value);
            // essay | single_choice | multiple_choice (ScreeningQuestionTypeEnum)
            $table->string('type');
            $table->boolean('is_required')->default(true);
            // Ordem de exibição da pergunta dentro do questionário
            $table->integer('order');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_questions');
    }
};
