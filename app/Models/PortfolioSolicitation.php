<?php

namespace App\Models;

use App\Enums\PortfolioSolicitationStatusEnum;
use App\Enums\PortfolioSolicitationTypeEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioSolicitation extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'dev_job_vacancy_id',
        'dev_profile_id',
        'portfolio_url',
        'type',
        'status',
        'due_date'
    ];

    /**
     * A solicitação nasce pendente, aguardando o envio do portfólio pelo desenvolvedor
     */
    protected $attributes = [
        'status' => PortfolioSolicitationStatusEnum::PENDING->value
    ];

    protected $casts = [
        'type' => PortfolioSolicitationTypeEnum::class,
        'status' => PortfolioSolicitationStatusEnum::class,
        'due_date' => 'date'
    ];

    public function devJobVacancy(): BelongsTo
    {
        return $this->belongsTo(DevJobVacancy::class, 'dev_job_vacancy_id');
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
