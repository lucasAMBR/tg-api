<?php

namespace App\Models;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DevJobVacancy extends Pivot
{

    use HasUuidV7;
    protected $table = 'dev_job_vacancy';

    protected $fillable = [
        'dev_profile_id',
        'job_vacancy_id',
        'status',
        'process_step',
        'feedback'
    ];

    protected $casts = [
        'status' => DevJobVacancyStatusEnum::class,
        'process_step' => SelectionProcessStageEnum::class
    ];

    public function jobVacancy(): BelongsTo {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function devProfile(): BelongsTo {
        return $this->belongsTo(DevProfile::class, 'dev_profile_id');
    }

    public function portfolioSolicitations(): HasMany {
        return $this->hasMany(PortfolioSolicitation::class, 'dev_job_vacancy_id');
    }

    /**
     * Solicitação de portfólio vigente da candidatura
     */
    public function portfolioSolicitation(): HasOne {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return $this->hasOne(PortfolioSolicitation::class, 'dev_job_vacancy_id')->latest('created_at');
    }

    public function screeningQuestionnaires(): HasMany {
        return $this->hasMany(DevScreeningQuestionnaire::class, 'dev_job_vacancy_id');
    }

    /**
     * Preenchimento vigente do questionário de triagem da candidatura
     */
    public function screeningQuestionnaire(): HasOne {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return $this->hasOne(DevScreeningQuestionnaire::class, 'dev_job_vacancy_id')->latest('created_at');
    }

    public function interviews(): HasMany {
        return $this->hasMany(DevJobVacancyInterview::class, 'dev_job_vacancy_id');
    }

    /**
     * Entrevista vigente da candidatura
     */
    public function interview(): HasOne {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return $this->hasOne(DevJobVacancyInterview::class, 'dev_job_vacancy_id')->latest('created_at');
    }
}
