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
        Schema::create('job_vacancy_process_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_vacancy_id');
            $table->string('step');
            $table->integer('order');
            $table->foreign('job_vacancy_id')->references('id')->on('job_vacancies')->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancy_process_steps');
    }
};
