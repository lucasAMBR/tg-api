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
        Schema::create('job_vacancy_soft_skills', function (Blueprint $table) {
            $table->foreignUuid('soft_skills_id')->constrained('soft_skills')->cascadeOnDelete();
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->primary(['job_vacancy_id', 'soft_skills_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_soft_skills');
    }
};
