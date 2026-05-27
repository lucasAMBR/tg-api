<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevProfile extends Model
{
    use HasFactory, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'cpf',
        'phone',
        'birthdate',
        'open_to_relocation',
        'open_to_work',
        'seniority_level',
        'specialty',
        'score'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function employment_histories(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    public function project_histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function academic_backgrounds(): HasMany
    {
        return $this->hasMany(AcademicBackground::class);
    }

    public function additional_courses(): HasMany
    {
        return $this->hasMany(AdditionalCourse::class);
    }

    public function dev_soft_skills(): HasMany
    {
        return $this->hasMany(DevSoftSkill::class);
    }

    public function recommendation_preference(): HasOne
    {
        return $this->hasOne(RecommendationPreference::class);
    }

}
