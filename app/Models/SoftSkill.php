<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftSkill extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'name',
        'i18n_name_key',
        'description',
        'i18n_description_key',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SoftSkillLevelResponse::class);
    }

    public function dev_soft_skill(): HasMany
    {
        return $this->hasMany(DevSoftSkill::class);
    }

    public function company_soft_skill(): HasMany {
        return $this->hasMany(CompanySoftSkill::class);
    }

    public function hasLevelResponse(string $responseId): bool
    {
        return $this->responses()->where('id', $responseId)->exists();
    }

    public function jobVacancies(): BelongsToMany {
        return $this->belongsToMany(JobVacancy::class,
            'job_vacancy_soft_skills',
            'soft_skills_id',
            'job_vacancy_id'
        );
    }
}
