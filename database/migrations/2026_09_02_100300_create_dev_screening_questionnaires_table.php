<?php

use App\Enums\DevScreeningQuestionnaireStatusEnum;
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
        // Preenchimento do questionário por uma candidatura. Nasce pendente quando a
        // candidatura entra na etapa de perguntas de triagem e passa a `answered`
        // quando o dev envia as respostas
        Schema::create('dev_screening_questionnaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('screening_questionnaire_id')->constrained('screening_questionnaires')->cascadeOnDelete();
            // Candidatura (dev x vaga). É por ela que sabemos o dev e, via vaga, a empresa
            $table->foreignUuid('dev_job_vacancy_id')->constrained('dev_job_vacancy')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('status')->default(DevScreeningQuestionnaireStatusEnum::PENDING->value);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Um único preenchimento por candidatura em cada questionário
            $table->unique(
                ['screening_questionnaire_id', 'dev_job_vacancy_id'],
                'dev_screening_questionnaires_questionnaire_apply_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_screening_questionnaires');
    }
};
