<?php

namespace App\Models;

use App\Enums\LanguageAssessmentQuestionTypeEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pergunta de uma prova de idioma. De escolha (com o gabarito nas alternativas) ou
 * escrita (com o gabarito na lista de respostas aceitas), conforme o `type`
 */
class LanguageAssessmentQuestion extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'language_assessment_id',
        'question',
        'type',
        'expected_answers',
        'weight',
        'order'
    ];

    protected $casts = [
        'type' => LanguageAssessmentQuestionTypeEnum::class,
        'expected_answers' => 'array',
        'weight' => 'integer',
        'order' => 'integer'
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(LanguageAssessment::class, 'language_assessment_id');
    }

    /**
     * Alternativas da pergunta. Sempre vazia nas perguntas escritas
     */
    public function options(): HasMany
    {
        return $this->hasMany(LanguageAssessmentQuestionOption::class, 'language_assessment_question_id')->orderBy('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DevLanguageAssessmentAnswer::class, 'language_assessment_question_id');
    }

    /**
     * Ids das alternativas certas da pergunta. É contra esse conjunto que a resposta
     * de escolha é corrigida
     *
     * @return array<int, string>
     */
    public function correctOptionIds(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->all();
    }
}
