<?php

use App\Enums\DevTechnicalChallengeStatusEnum;
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
        // Entrega do desafio técnico por uma candidatura. Nasce pendente quando a
        // candidatura entra na etapa de desafio técnico e passa a `delivered` quando
        // o dev envia o repositório
        Schema::create('dev_technical_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('technical_challenge_id')->constrained('technical_challenges')->cascadeOnDelete();
            // Candidatura (dev x vaga). É por ela que sabemos o dev e, via vaga, a empresa
            $table->foreignUuid('dev_job_vacancy_id')->constrained('dev_job_vacancy')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('status')->default(DevTechnicalChallengeStatusEnum::PENDING->value);
            // Repositório com a solução, enviado pelo dev
            $table->string('repository_url')->nullable();
            // Cópia do prazo do desafio no momento em que a candidatura entrou na etapa
            $table->date('due_date');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // Uma única entrega por candidatura em cada desafio
            $table->unique(
                ['technical_challenge_id', 'dev_job_vacancy_id'],
                'dev_technical_challenges_challenge_apply_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_technical_challenges');
    }
};
