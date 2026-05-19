<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{

    use HasUuidV7, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'is_official',
        'is_approved'
    ];

    public function project_histories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectHistory::class);
    }

    public function company_projects(): BelongsToMany {
        return $this->belongsToMany(CompanyProject::class, 'language_company_project');
    }

    public function jobVacancies(): BelongsToMany {
        return $this->belongsToMany(JobVacancy::class,
            'job_vacancy_languages',
            'languages_id',
            'job_vacancy_id'
        )
        ->using(JobVacancyLanguage::class)
        ->withPivot('language_level');
    }

    public function company_profiles(): BelongsToMany
    {
        return $this->belongsToMany(CompanyProfile::class);
    }

    public function blacklistedInPreferences(): BelongsToMany
    {
        return $this->belongsToMany(
            RecommendationPreference::class,
            'recommendation_preferences_black_list',
            'language_id',
            'recommendation_preference_id'
        );
    }
}
