<?php

namespace App\Models;

use App\Casts\Vector;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancyEmbedding extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'embedding'
    ];

    protected $casts = [
        'embedding' => Vector::class,
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }
}
