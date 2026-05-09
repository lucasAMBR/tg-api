<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecommendationPreference extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        "allow_clt",
        'allow_contractor',
        'allow_internship',
        'allow_on_site',
        'allow_hybrid',
        'allow_remote',
        'on_site_job_radius',
        'hybrid_jobs_radius',
        'allow_stack_flexibility',
        'min_remuneration',
        'dev_profile_id'
    ];

    public $with = ['blackListedLanguages'];

    public $casts = [
        "allow_clt" => 'boolean',
        'allow_contractor'=> 'boolean',
        'allow_internship' => 'boolean',
        'allow_on_site' => 'boolean',
        'allow_hybrid' => 'boolean',
        'allow_remote' => 'boolean',
        'allow_stack_flexibility' => 'boolean',
        'on_site_job_radius' => 'integer',
        'hybrid_jobs_radius' => 'integer',
        'min_remuneration' => 'decimal:2',
    ];

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }

    public function blackListedLanguages(): BelongsToMany
    {
        return $this->belongsToMany(
            Language::class,
            'recommendation_preferences_black_list',
            'recommendation_preference_id',
            'language_id'
        );
    }
}
