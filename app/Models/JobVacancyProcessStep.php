<?php

namespace App\Models;

use App\Enums\SelectionProcessStageEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancyProcessStep extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'job_vacancy_id',
        'step',
        'order'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }

    /**
     * Organiza as etapas no padrão do processo seletivo: toda vaga começa pela
     * triagem de currículos, então ela é sempre a primeira etapa e as demais
     * seguem depois, na ordem informada
     *
     * @param iterable<int, array{step: string, order: int}> $processSteps
     * @return list<array{step: string, order: int}>
     */
    public static function withResumeScreeningFirst(iterable $processSteps): array
    {
        return collect($processSteps)
            ->reject(fn(array $processStep) => $processStep['step'] === SelectionProcessStageEnum::RESUME_SCREENING->value)
            ->sortBy('order')
            ->pluck('step')
            ->prepend(SelectionProcessStageEnum::RESUME_SCREENING->value)
            ->values()
            ->map(fn(string $step, int $index) => [
                'step' => $step,
                'order' => $index + 1
            ])
            ->all();
    }
}
