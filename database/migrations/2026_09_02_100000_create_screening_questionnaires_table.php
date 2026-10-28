<?php

use App\Enums\TranslationStatusEnum;
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
        Schema::create('screening_questionnaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Uma vaga tem um único questionário de triagem vigente. A unicidade é
            // garantida no service, e não por índice, porque o soft delete permite
            // que questionários antigos da mesma vaga continuem na tabela
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_pt')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_pt')->nullable();
            $table->text('description_en')->nullable();
            $table->string('translation_status')->default(TranslationStatusEnum::PENDING->value);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_questionnaires');
    }
};
