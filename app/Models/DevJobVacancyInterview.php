<?php

namespace App\Models;

use App\Enums\DevJobVacancyInterviewStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevJobVacancyInterview extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'dev_job_vacancy_id',
        'title',
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
        'status' => DevJobVacancyInterviewStatusEnum::AWAITING_SCHEDULE->value,
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_in_minutes' => 'integer',
        'status' => DevJobVacancyInterviewStatusEnum::class,
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
}
