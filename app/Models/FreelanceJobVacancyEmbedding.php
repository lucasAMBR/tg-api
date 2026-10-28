<?php

namespace App\Models;

use App\Casts\Vector;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreelanceJobVacancyEmbedding extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'freelance_job_vacancy_id',
        'embedding'
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }

    public function freelanceJobVacancy(): BelongsTo
    {
        return $this->belongsTo(FreelanceJobVacancy::class);
    }
}
