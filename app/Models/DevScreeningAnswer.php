<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Resposta do dev a uma pergunta do questionário de triagem: `response` nas
 * dissertativas, `options` nas de escolha única e de múltipla escolha
 */
class DevScreeningAnswer extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'dev_screening_questionnaire_id',
        'screening_question_id',
        'response'
    ];

    public function devQuestionnaire(): BelongsTo
    {
        return $this->belongsTo(DevScreeningQuestionnaire::class, 'dev_screening_questionnaire_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ScreeningQuestion::class, 'screening_question_id');
    }

    /**
     * Opções marcadas pelo dev. Sempre vazia nas dissertativas
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            ScreeningQuestionOption::class,
            'dev_screening_answer_options',
            'dev_screening_answer_id',
            'screening_question_option_id'
        )->withTimestamps();
    }
}
