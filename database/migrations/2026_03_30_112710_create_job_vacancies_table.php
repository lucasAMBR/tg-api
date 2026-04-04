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
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->text('description');
            $table->string('employment_type'); // enum
            $table->string('benefits');
            $table->decimal('estimated_salary', 8,2);
            $table->string('contract_type'); // enum
            $table->string('seniority_level'); // enum

            $table->timestamps();
        });

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_vacancies');
    }
};
