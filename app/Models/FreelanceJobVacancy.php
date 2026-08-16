<?php

namespace App\Models;

use App\Enums\FreelanceJobTypeEnum;
use App\Enums\SalaryTypeEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreelanceJobVacancy extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'description',
        'job_type',
        'salary_type',
        'estimated_salary',
        'client_profile_id',
        'specialties',
        'seniority_level'
    ];

    protected function casts(): array
    {
        return [
            'title' => 'string',
            'description' => 'string',
            'job_type' => FreelanceJobTypeEnum::class,
            'salary_type' => SalaryTypeEnum::class,
            'estimated_salary' => 'decimal:2',
            'client_profile_id' => 'string',
            'specialties' => 'array',
            'seniority_level' => SeniorityLevelEnum::class
        ];
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class,
            'freelance_job_vacancy_languages', // Nome da tabela caso não esteja na convenção
            'freelance_job_vacancy_id', // Id do campo relacionado a esse model
            'language_id' // Id do campo relacionado ao outro model relacionado
        )
        ->using(FreelanceJobVacancyLanguage::class)
        ->withPivot('language_level')
        ->withTimestamps();
    }

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class);
    }
}
