<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Alternativa de uma pergunta de escolha da prova de idioma. O `is_correct` é o
 * gabarito e só é exposto para a empresa, ou para o dev depois da entrega
 */
class LanguageAssessmentQuestionOption extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'language_assessment_question_id',
        'option',
        'is_correct',
        'order'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'order' => 'integer'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(LanguageAssessmentQuestion::class, 'language_assessment_question_id');
    }

    /**
     * Respostas de devs em que essa alternativa foi marcada
     */
    public function answers(): BelongsToMany
    {
        return $this->belongsToMany(
            DevLanguageAssessmentAnswer::class,
            'dev_language_assessment_answer_options',
            'language_assessment_question_option_id',
            'dev_language_assessment_answer_id'
        )->withTimestamps();
    }
}
