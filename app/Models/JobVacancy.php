<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\ContractType;
use App\Enums\DevSpecialtyEnum;
use App\Enums\EmploymentType;
use App\Enums\JobVacancyStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Enums\SeniorityLevelEnum;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobVacancy extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'title_pt',
        'title_en',
        'description',
        'description_pt',
        'description_en',
        'employment_type',
        'benefits',
        'benefits_pt',
        'benefits_en',
        'translation_status',
        'estimated_salary',
        'contract_type',
        'seniority_level',
        'specialties',
        'status',
        'process_step',
        'company_profile_id'
    ];

    /**
     * Toda vaga nasce com as inscrições abertas e aguardando a triagem de currículos
     */
    protected $attributes = [
        'status' => JobVacancyStatusEnum::OPEN_INSCRIPTIONS->value,
        'process_step' => SelectionProcessStageEnum::AWAITING_RESUME_SCREENING->value
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'contract_type' => ContractType::class,
        'seniority_level' => SeniorityLevelEnum::class,
        'benefits' => 'array',
        'benefits_pt' => 'array',
        'benefits_en' => 'array',
        'specialties' => DevSpecialtyEnum::class,
        'status' => JobVacancyStatusEnum::class,
        'process_step' => SelectionProcessStageEnum::class
    ];

    public function getTranslatableContent(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'benefits' => $this->benefits ?? []
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'title_pt' => $translatedData['title']['pt'],
            'title_en' => $translatedData['title']['en'],
            'description_pt' => $translatedData['description']['pt'],
            'description_en' => $translatedData['description']['en'],
            'benefits_pt' => $translatedData['benefits']['pt'],
            'benefits_en' => $translatedData['benefits']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

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

    public function devProfiles(): BelongsToMany {
        return $this->belongsToMany(DevProfile::class, 
            'dev_job_vacancy',
            'job_vacancy_id',
            'dev_profile_id'
        )
        ->using(DevJobVacancy::class)
        ->withPivot('status', 'process_step', 'feedback')
        ->withTimestamps();
    }

    public function companyProfile(): BelongsTo {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function processSteps(): HasMany {
        return $this->hasMany(JobVacancyProcessStep::class)->orderBy('order');
    }

    public function applications(): HasMany {
        return $this->hasMany(DevJobVacancy::class, 'job_vacancy_id');
    }

    public function screeningQuestionnaires(): HasMany {
        return $this->hasMany(ScreeningQuestionnaire::class, 'job_vacancy_id');
    }

    /**
     * Questionário de triagem vigente da vaga
     */
    public function screeningQuestionnaire(): HasOne {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid
        return $this->hasOne(ScreeningQuestionnaire::class, 'job_vacancy_id')->latest('created_at');
    }

    /**
     * Indica se a vaga ainda recebe novas candidaturas
     */
    public function acceptsApplications(): bool {
        return $this->status?->acceptsApplications() ?? false;
    }

    /**
     * Limita a consulta às vagas com inscrições abertas
     */
    public function scopeOpenForApplications(Builder $query): Builder {
        return $query->where('status', JobVacancyStatusEnum::OPEN_INSCRIPTIONS);
    }

}
