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
        // Alternativas das perguntas de escolha única e de múltipla escolha. A
        // pergunta dissertativa não tem opções
        Schema::create('screening_question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('screening_question_id')->constrained('screening_questions')->cascadeOnDelete();
            $table->text('option');
            $table->text('option_pt')->nullable();
            $table->text('option_en')->nullable();
            $table->string('translation_status')->default(TranslationStatusEnum::PENDING->value);
            // Ordem de exibição da opção dentro da pergunta
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
        Schema::dropIfExists('screening_question_options');
    }
};
