<?php

namespace App\Models;

use App\Enums\DevLanguageAssessmentStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prova de idioma de uma candidatura. Nasce pendente quando a candidatura entra na
 * etapa de avaliação de idioma e guarda as respostas e a nota da correção
 */
class DevLanguageAssessment extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'language_assessment_id',
        'dev_job_vacancy_id',
        'dev_profile_id',
        'status',
        'score',
        'max_score',
        'passed',
        'due_date',
        'submitted_at'
    ];

    /**
     * A prova nasce pendente, aguardando a entrega do dev
     */
    protected $attributes = [
        'status' => DevLanguageAssessmentStatusEnum::PENDING->value
    ];

    protected $casts = [
        'status' => DevLanguageAssessmentStatusEnum::class,
        'score' => 'integer',
        'max_score' => 'integer',
        'passed' => 'boolean',
        'due_date' => 'date',
        'submitted_at' => 'datetime'
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(LanguageAssessment::class, 'language_assessment_id');
    }

    public function devJobVacancy(): BelongsTo
    {
        return $this->belongsTo(DevJobVacancy::class, 'dev_job_vacancy_id');
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class, 'dev_profile_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DevLanguageAssessmentAnswer::class, 'dev_language_assessment_id');
    }

    /**
     * Indica se o dev já entregou a prova — e, portanto, se ela já foi corrigida
     */
    public function isAnswered(): bool
    {
        return $this->status === DevLanguageAssessmentStatusEnum::ANSWERED;
    }
}
