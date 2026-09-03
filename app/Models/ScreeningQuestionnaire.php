<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Questionário de triagem da vaga, respondido pelos candidatos na etapa
 * `screening_questions` do processo seletivo
 */
class ScreeningQuestionnaire extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'job_vacancy_id',
        'title',
        'title_pt',
        'title_en',
        'description',
        'description_pt',
        'description_en',
        'translation_status'
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value
    ];

    public function getTranslatableContent(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description ?? ''
        ];
    }

    public function applyTranslation(array $translatedData): void
    {
        $this->update([
            'title_pt' => $translatedData['title']['pt'],
            'title_en' => $translatedData['title']['en'],
            'description_pt' => $translatedData['description']['pt'],
            'description_en' => $translatedData['description']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value
        ]);
    }

    public function updateTranslationStatus(string $status): void
    {
        $this->update([
            'translation_status' => $status
        ]);
    }

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ScreeningQuestion::class, 'screening_questionnaire_id')->orderBy('order');
    }

    /**
     * Preenchimentos do questionário, um por candidatura que chegou à etapa
     */
    public function devQuestionnaires(): HasMany
    {
        return $this->hasMany(DevScreeningQuestionnaire::class, 'screening_questionnaire_id');
    }
}
