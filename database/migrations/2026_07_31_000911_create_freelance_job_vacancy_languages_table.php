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
        Schema::create('freelance_job_vacancy_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('freelance_job_vacancy_id');
            $table->foreign('freelance_job_vacancy_id')->references('id')->on('freelance_job_vacancies')->cascadeOnDelete();

            $table->uuid('language_id');
            $table->foreign('language_id')->references('id')->on('languages')->cascadeOnDelete();

            $table->string('language_level');

            $table->unique(['freelance_job_vacancy_id', 'language_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_job_vacancy_languages');
    }
};
