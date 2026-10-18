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
        // Desafio técnico da vaga, montado pela empresa. É o que a etapa
        // `technical_challenge` exige para começar: sem ele as candidaturas ficam
        // paradas em `awaiting_technical_challenge`
        Schema::create('technical_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Uma vaga tem um único desafio técnico vigente. A unicidade é garantida
            // no service, e não por índice, porque o soft delete permite que desafios
            // antigos da mesma vaga continuem na tabela
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_pt')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description');
            $table->text('description_pt')->nullable();
            $table->text('description_en')->nullable();
            // Regras da entrega: o que pode ser usado, o que será avaliado, limites
            $table->text('rules')->nullable();
            $table->text('rules_pt')->nullable();
            $table->text('rules_en')->nullable();
            $table->string('translation_status')->default(TranslationStatusEnum::PENDING->value);
            // Prazo de entrega definido pela empresa, herdado por cada candidatura que
            // entra na etapa
            $table->date('due_date');
            // Quando a empresa aceita o envio do projeto compactado além do repositório
            $table->boolean('accepts_zip_submission')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_challenges');
    }
};
