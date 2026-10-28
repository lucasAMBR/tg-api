<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\ScreeningQuestionTypeEnum;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pergunta de um questionário de triagem. Dissertativa, de escolha única ou de
 * múltipla escolha, conforme o `type`
 */
class ScreeningQuestion extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'screening_questionnaire_id',
        'question',
        'question_pt',
        'question_en',
        'translation_status',
        'type',
        'is_required',
        'order'
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value
    ];

    protected $casts = [
        'type' => ScreeningQuestionTypeEnum::class,
        'is_required' => 'boolean',
        'order' => 'integer'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'question' => $this->question
        ];
    }

    public function applyTranslation(array $translatedData): void
    {
        $this->update([
            'question_pt' => $translatedData['question']['pt'],
            'question_en' => $translatedData['question']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value
        ]);
    }

    public function updateTranslationStatus(string $status): void
    {
        $this->update([
            'translation_status' => $status
        ]);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(ScreeningQuestionnaire::class, 'screening_questionnaire_id');
    }

    /**
     * Alternativas da pergunta. Sempre vazia nas dissertativas
     */
    public function options(): HasMany
    {
        return $this->hasMany(ScreeningQuestionOption::class, 'screening_question_id')->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DevScreeningAnswer::class, 'screening_question_id');
    }
}
