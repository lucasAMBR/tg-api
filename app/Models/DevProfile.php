<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevProfile extends Model implements Translatable
{
    use HasFactory, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'bio_pt',
        'bio_en',
        'translation_status',
        'cpf',
        'phone',
        'birthdate',
        'open_to_relocation',
        'open_to_work',
        'seniority_level',
        'seniority_tested',
        'specialty',
        'score'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'bio' => $this->bio
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'bio_pt' => $translatedData['bio']['pt'],
            'bio_en' => $translatedData['bio']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    protected $casts = [
        'open_to_work' => 'boolean',
        'open_to_relocation' => 'boolean',
        'seniority_tested' => 'boolean',
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

    public function hard_skills(): HasMany
    {
        return $this->hasMany(HardSkill::class);
    }

    public function recommendation_preference(): HasOne
    {
        return $this->hasOne(RecommendationPreference::class);
    }

    public function jobVacancies(): BelongsToMany {
        return $this->belongsToMany(JobVacancy::class,
            'dev_job_vacancy',
            'dev_profile_id',
            'job_vacancy_id'
        )
        ->using(DevJobVacancy::class)
        ->withPivot('status', 'feedback')
        ->withTimestamps();
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}
