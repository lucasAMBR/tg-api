<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Alternativa de uma pergunta de escolha única ou de múltipla escolha
 */
class ScreeningQuestionOption extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'screening_question_id',
        'option',
        'option_pt',
        'option_en',
        'translation_status',
        'order'
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'option' => $this->option
        ];
    }

    public function applyTranslation(array $translatedData): void
    {
        $this->update([
            'option_pt' => $translatedData['option']['pt'],
            'option_en' => $translatedData['option']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value
        ]);
    }

    public function updateTranslationStatus(string $status): void
    {
        $this->update([
            'translation_status' => $status
        ]);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ScreeningQuestion::class, 'screening_question_id');
    }

    /**
     * Respostas de devs em que essa opção foi marcada
     */
    public function answers(): BelongsToMany
    {
        return $this->belongsToMany(
            DevScreeningAnswer::class,
            'dev_screening_answer_options',
            'screening_question_option_id',
            'dev_screening_answer_id'
        )->withTimestamps();
    }
}
