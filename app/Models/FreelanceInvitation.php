<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convite do cliente para um dev enviar orçamento no freela
 */
class FreelanceInvitation extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'freelance_job_vacancy_id',
        'dev_profile_id'
    ];

    public function freelanceJobVacancy(): BelongsTo
    {
        return $this->belongsTo(FreelanceJobVacancy::class);
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
