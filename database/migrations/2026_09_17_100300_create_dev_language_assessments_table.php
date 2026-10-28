<?php

use App\Enums\DevLanguageAssessmentStatusEnum;
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
        // Prova de um candidato. Nasce pendente quando a candidatura entra na etapa de
        // avaliação de idioma e é corrigida na hora da entrega
        Schema::create('dev_language_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('language_assessment_id')->constrained('language_assessments')->cascadeOnDelete();
            // Candidatura (dev x vaga). É por ela que sabemos o dev e, via vaga, a empresa
            $table->foreignUuid('dev_job_vacancy_id')->constrained('dev_job_vacancy')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('status')->default(DevLanguageAssessmentStatusEnum::PENDING->value);
            // Resultado da correção automática, preenchido na entrega
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            // Veredito contra o `passing_score` da prova, congelado na correção. Fica
            // null quando a empresa não definiu nota de corte
            $table->boolean('passed')->nullable();
            $table->date('due_date');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Uma única prova por candidatura em cada avaliação
            $table->unique(
                ['language_assessment_id', 'dev_job_vacancy_id'],
                'dev_language_assessments_assessment_apply_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_language_assessments');
    }
};
