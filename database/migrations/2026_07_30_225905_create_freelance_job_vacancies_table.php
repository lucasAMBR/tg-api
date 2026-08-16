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
        Schema::create('freelance_job_vacancies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->text('description');
            $table->string('job_type');
            $table->string('salary_type');
            $table->decimal('estimated_salary', 8, 2);
            $table->json('specialties');
            $table->string('seniority_level');

            $table->uuid('client_profile_id');
            $table->foreign('client_profile_id')->references('id')->on('client_profiles')->cascadeOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_job_vacancies');
    }
};
