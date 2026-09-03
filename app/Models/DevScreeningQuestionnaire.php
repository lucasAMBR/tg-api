<?php

namespace App\Models;

use App\Enums\DevScreeningQuestionnaireStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Preenchimento do questionário de triagem por uma candidatura. Nasce pendente
 * quando a candidatura entra na etapa de perguntas de triagem e guarda as
 * respostas enviadas pelo dev
 */
class DevScreeningQuestionnaire extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'screening_questionnaire_id',
        'dev_job_vacancy_id',
        'dev_profile_id',
        'status',
        'due_date',
        'submitted_at'
    ];

    /**
     * O preenchimento nasce pendente, aguardando o envio das respostas pelo dev
     */
    protected $attributes = [
        'status' => DevScreeningQuestionnaireStatusEnum::PENDING->value
    ];

    protected $casts = [
        'status' => DevScreeningQuestionnaireStatusEnum::class,
        'due_date' => 'date',
        'submitted_at' => 'datetime'
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(ScreeningQuestionnaire::class, 'screening_questionnaire_id');
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
        return $this->hasMany(DevScreeningAnswer::class, 'dev_screening_questionnaire_id');
    }

    /**
     * Indica se o dev já enviou as respostas
     */
    public function isAnswered(): bool
    {
        return $this->status === DevScreeningQuestionnaireStatusEnum::ANSWERED;
    }
}
