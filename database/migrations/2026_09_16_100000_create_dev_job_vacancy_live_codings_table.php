<?php

use App\Enums\DevJobVacancyLiveCodingStatusEnum;
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
        // Live coding da candidatura, no mesmo desenho da entrevista: negociação de
        // horário entre dev e empresa e, depois, a call P2P. O que ele tem a mais é o
        // enunciado do exercício, escrito pela empresa antes da call
        Schema::create('dev_job_vacancy_live_codings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            // Candidatura (dev x vaga). É por ela que sabemos o dev e, via vaga, a empresa
            $table->foreignUuid('dev_job_vacancy_id')->constrained('dev_job_vacancy')->cascadeOnDelete();
            $table->string('title');
            // Enunciado do exercício, preenchido pela empresa. Fica visível para o dev
            // antes da call
            $table->text('statement')->nullable();
            // Linguagem/stack esperada na resolução, quando a empresa define uma
            $table->string('programming_language')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedSmallInteger('duration_in_minutes')->nullable();
            $table->string('status')->default(DevJobVacancyLiveCodingStatusEnum::AWAITING_SCHEDULE->value);
            // Preenchidos quando os participantes entram/saem da call, para sabermos
            // se ela realmente aconteceu (independente do status ter ficado approved)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_job_vacancy_live_codings');
    }
};
