<?php

namespace App\Models;

use App\Enums\HardSkillLevelsEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FreelanceJobVacancyLanguage extends Pivot
{
    use HasUuidV7;

    protected $table = 'freelance_job_vacancy_languages';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'freelance_job_vacancy_id',
        'language_id',
        'language_level'
    ];

    protected $casts = [
        'language_level' => HardSkillLevelsEnum::class
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function freelanceJobVacancy(): BelongsTo
    {
        return $this->belongsTo(FreelanceJobVacancy::class);
    }
}
