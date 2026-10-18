<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Resposta do dev a uma pergunta da prova, já com o resultado da correção
 * automática: `response` nas perguntas escritas, `options` nas de escolha
 */
class DevLanguageAssessmentAnswer extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'dev_language_assessment_id',
        'language_assessment_question_id',
        'response',
        'is_correct',
        'earned_score'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'earned_score' => 'integer'
    ];

    public function devAssessment(): BelongsTo
    {
        return $this->belongsTo(DevLanguageAssessment::class, 'dev_language_assessment_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(LanguageAssessmentQuestion::class, 'language_assessment_question_id');
    }

    /**
     * Alternativas marcadas pelo dev. Sempre vazia nas perguntas escritas
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            LanguageAssessmentQuestionOption::class,
            'dev_language_assessment_answer_options',
            'dev_language_assessment_answer_id',
            'language_assessment_question_option_id'
        )->withTimestamps();
    }
}
