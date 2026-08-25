<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('freelance_job_vacancy_embeddings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('freelance_job_vacancy_id');
            $table->foreign('freelance_job_vacancy_id')->references('id')->on('freelance_job_vacancies')->cascadeOnUpdate();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE freelance_job_vacancy_embeddings ADD COLUMN embedding vector(1536)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_job_vacancy_embeddings');
    }
};
