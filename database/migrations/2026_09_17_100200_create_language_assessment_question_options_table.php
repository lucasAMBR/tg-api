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
        Schema::create('language_assessment_question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('language_assessment_question_id')->constrained('language_assessment_questions')->cascadeOnDelete();
            $table->text('option');
            // O gabarito da pergunta. Nunca vai para o dev antes da entrega da prova
            $table->boolean('is_correct')->default(false);
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
        Schema::dropIfExists('language_assessment_question_options');
    }
};
