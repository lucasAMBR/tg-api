<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convite da empresa para um dev participar do processo seletivo da vaga
 */
class JobVacancyInvitation extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'dev_profile_id'
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
