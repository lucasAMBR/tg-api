<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prova de idioma da vaga, respondida pelos candidatos na etapa
 * `language_assessment` do processo seletivo.
 *
 * Diferente do questionário de triagem, a prova tem gabarito: cada pergunta sabe
 * qual é a resposta certa e a correção é automática, feita na entrega.
 *
 * O conteúdo não é traduzido de propósito — a prova é escrita no idioma avaliado
 */
class LanguageAssessment extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'job_vacancy_id',
        'title',
        'description',
        'language',
        'passing_score',
        'due_date'
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'due_date' => 'date'
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LanguageAssessmentQuestion::class, 'language_assessment_id')->orderBy('order');
    }

    /**
     * Provas dos candidatos, uma por candidatura que chegou à etapa
     */
    public function devAssessments(): HasMany
    {
        return $this->hasMany(DevLanguageAssessment::class, 'language_assessment_id');
    }

    /**
     * Nota máxima da prova: a soma do peso de todas as perguntas
     */
    public function maxScore(): int
    {
        return (int) $this->questions()->sum('weight');
    }
}
