<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyProfile extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'bio_pt',
        'bio_en',
        'translation_status',
        'cnpj',
        'phone',
        'founding_date',
        'operational_segment',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function company_projects(): HasMany {
        return $this->hasMany(CompanyProject::class);
    }

    public function company_soft_skills(): HasMany {
        return $this->hasMany(CompanySoftSkill::class, 'company_profile_id');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    public function jobVacancies(): HasMany {
        return $this->hasMany(JobVacancy::class);
    }

}
