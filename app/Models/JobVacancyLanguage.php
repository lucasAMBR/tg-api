<?php

namespace App\Models;

use App\Enums\HardSkillLevelsEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobVacancyLanguage extends Pivot
{
    use HasUuidV7;

    protected $table = 'job_vacancy_languages';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'languages_id',
        'job_vacancy_id',
        'language_level'
    ];

    protected $casts = [
        'language_level' => HardSkillLevelsEnum::class
    ];

    public function languages(): BelongsTo {
        return $this->belongsTo(Language::class);
    }

    public function jobVacancy(): BelongsTo {
        return $this->belongsTo(JobVacancy::class);
    }
}
