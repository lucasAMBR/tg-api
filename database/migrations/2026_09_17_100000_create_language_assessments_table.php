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
        // Prova de idioma da vaga, montada pela empresa. É o que a etapa
        // `language_assessment` exige para começar: sem ela as candidaturas ficam
        // paradas em `awaiting_language_assessment`
        //
        // NOTA: diferente do questionário de triagem e do desafio técnico, o conteúdo
        // aqui NÃO é traduzido. A prova é escrita no idioma que está sendo avaliado —
        // traduzir o enunciado acabaria com o sentido da avaliação
        Schema::create('language_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Uma vaga tem uma única prova vigente. A unicidade é garantida no service,
            // e não por índice, porque o soft delete permite que provas antigas da
            // mesma vaga continuem na tabela
            $table->foreignUuid('job_vacancy_id')->constrained('job_vacancies')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Idioma avaliado, escrito pela empresa (ex.: "Inglês", "Español"). Não usa
            // a tabela `languages`, que guarda linguagens de programação
            $table->string('language');
            // Nota mínima de aprovação. Sem ela a prova só pontua, sem veredito
            $table->unsignedSmallInteger('passing_score')->nullable();
            $table->date('due_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_assessments');
    }
};
