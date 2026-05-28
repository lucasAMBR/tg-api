<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\SeniorityLevelEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobVacancy extends Model
{
    use HasUuidV7;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'description',
        'employment_type',
        'benefits',
        'estimated_salary',
        'contract_type',
        'seniority_level',
        'specialties',
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'contract_type' => ContractType::class,
        'seniority_level' => SeniorityLevelEnum::class,
        'benefits' => 'array',
        'specialties' => 'array'
    ];

    public function languages(): BelongsToMany {
        return $this->belongsToMany(Language::class,
            'job_vacancy_languages', // Nome da tabela caso não esteja na convenção
            'job_vacancy_id', // Id do campo relacionado a esse model
            'languages_id' // Id do campo relacionado ao outro model relacionado
        )
        ->using(JobVacancyLanguage::class)
        ->withPivot('language_level');
    }

    public function desirableLanguage(): BelongsToMany {
        return $this->belongsToMany(Language::class,
            'job_vacancy_languages_desirables',
            'job_vacancy_id',
            'language_id'
        );
    }

    public function softSkill(): BelongsToMany {
        return $this->belongsToMany(SoftSkill::class,
            'job_vacancy_soft_skills',
            'job_vacancy_id',
            'soft_skills_id'
        );
    }

}
