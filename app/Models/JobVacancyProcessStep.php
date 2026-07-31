<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancyProcessStep extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'step',
        'order'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }
}
