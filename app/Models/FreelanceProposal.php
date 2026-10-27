<?php

namespace App\Models;

use App\Enums\FreelanceProposalPricingTypeEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Orçamento de um dev para um freela. O cliente escolhe um deles, e é isso que fecha
 * o contrato do freela
 */
class FreelanceProposal extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'freelance_job_vacancy_id',
        'dev_profile_id',
        'pricing_type',
        'total_price',
        'hourly_rate',
        'estimated_hours',
        'message',
        'status'
    ];

    /**
     * Mesmo default da coluna, para o model recém-criado (ainda sem refresh) já ter o status
     */
    protected $attributes = [
        'status' => FreelanceProposalStatusEnum::PENDING->value
    ];

    protected function casts(): array
    {
        return [
            'pricing_type' => FreelanceProposalPricingTypeEnum::class,
            'total_price' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'estimated_hours' => 'integer',
            'status' => FreelanceProposalStatusEnum::class
        ];
    }

    public function freelanceJobVacancy(): BelongsTo
    {
        return $this->belongsTo(FreelanceJobVacancy::class);
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
