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
        Schema::create('dev_job_vacancy', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();

            $table->string('status');
            $table->text('feedback')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_job_vacancy');
    }
};
