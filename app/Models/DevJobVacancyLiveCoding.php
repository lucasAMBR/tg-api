<?php

namespace App\Models;

use App\Enums\DevJobVacancyLiveCodingStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Live coding de uma candidatura na etapa `live_coding` do processo seletivo.
 * Segue o mesmo desenho da entrevista — negociação de horário e call P2P — com o
 * enunciado do exercício por cima
 */
class DevJobVacancyLiveCoding extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'dev_job_vacancy_id',
        'title',
        'statement',
        'programming_language',
        'scheduled_at',
        'duration_in_minutes',
        'status',
        'started_at',
        'ended_at',
    ];

    /**
     * Nasce sem horário definido, aguardando a primeira proposta
     */
    protected $attributes = [
        'status' => DevJobVacancyLiveCodingStatusEnum::AWAITING_SCHEDULE->value,
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_in_minutes' => 'integer',
        'status' => DevJobVacancyLiveCodingStatusEnum::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function devJobVacancy(): BelongsTo
    {
        return $this->belongsTo(DevJobVacancy::class);
    }

    /**
     * Indica se a empresa já publicou o enunciado do exercício
     */
    public function hasStatement(): bool
    {
        return filled($this->statement);
    }
}
