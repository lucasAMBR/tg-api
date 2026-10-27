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
        // Convites do cliente para um dev enviar orçamento no freela
        Schema::create('freelance_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('freelance_job_vacancy_id')->constrained('freelance_job_vacancies')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['freelance_job_vacancy_id', 'dev_profile_id']);
            $table->index('dev_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_invitations');
    }
};
