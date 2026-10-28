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
        Schema::create('language_assessment_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('language_assessment_id')->constrained('language_assessments')->cascadeOnDelete();
            $table->text('question');
            // single_choice, multiple_choice ou text
            $table->string('type');
            // Respostas aceitas nas perguntas escritas, comparadas sem diferenciar
            // caixa nem espaço repetido. É o gabarito das perguntas de texto, então
            // nunca vai para o dev antes da entrega
            $table->json('expected_answers')->nullable();
            // Quanto a pergunta vale na nota final
            $table->unsignedSmallInteger('weight')->default(1);
            $table->unsignedSmallInteger('order');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_assessment_questions');
    }
};
