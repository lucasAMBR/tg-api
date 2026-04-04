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
        Schema::create('job_vacancy_languages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('languages_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('language_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_languages');
    }
};
