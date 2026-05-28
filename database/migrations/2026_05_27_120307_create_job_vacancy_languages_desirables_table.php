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
        Schema::create('job_vacancy_languages_desirables', function (Blueprint $table) {
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->primary(['language_id', 'job_vacancy_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_languages_desirables');
    }
};
