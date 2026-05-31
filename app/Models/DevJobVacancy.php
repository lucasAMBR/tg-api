<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DevJobVacancy extends Pivot
{

    use HasUuidV7;
    protected $table = 'dev_job_vacancy';

    protected $fillable = [
        'dev_profile_id',
        'job_vacancy_id',
        'status',
        'feedback'
    ];

    public function jobVacancy(): BelongsTo {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function devProfile(): BelongsTo {
        return $this->belongsTo(DevProfile::class, 'dev_profile_id');
    }
}
