<?php

namespace App\Models;

use App\Enums\FreelanceJobTypeEnum;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'budget',
        'status',
        'contracted_at',
        'completed_at',
        'client_profile_id',
        'specialties',
        'seniority_level'
    ];

    /**
     * Mesmo default da coluna, para o model recém-criado (ainda sem refresh) já ter o status
     */
    protected $attributes = [
        'status' => FreelanceJobVacancyStatusEnum::OPEN->value
    ];

    protected function casts(): array
    {
        return [
            'title' => 'string',
            'description' => 'string',
            'job_type' => FreelanceJobTypeEnum::class,
            'budget' => 'decimal:2',
            'status' => FreelanceJobVacancyStatusEnum::class,
            'contracted_at' => 'datetime',
            'completed_at' => 'datetime',
            'client_profile_id' => 'string',
            'specialties' => 'array',
            'seniority_level' => SeniorityLevelEnum::class
        ];
    }

    public function acceptsProposals(): bool
    {
        return $this->status?->acceptsProposals() ?? false;
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

    public function proposals(): HasMany
    {
        return $this->hasMany(FreelanceProposal::class);
    }

    /**
     * O orçamento escolhido pelo cliente, que fechou o contrato do freela
     */
    public function acceptedProposal(): HasOne
    {
        return $this->hasOne(FreelanceProposal::class)
            ->where('status', FreelanceProposalStatusEnum::ACCEPTED);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FreelanceInvitation::class);
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(FreelanceFeedPost::class);
    }

    public function embedding(): HasOne
    {
        return $this->hasOne(FreelanceJobVacancyEmbedding::class);
    }
}
