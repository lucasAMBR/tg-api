<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        "question",
        "question_en",
        "question_pt",
        "translation_status",
        "difficulty_level",
        "language_id",
        "category",
        "ideal_time_to_solve",
        "code_snippet",
        "is_multiple_choice",
        "seniority_level"
    ];

    public function getTranslatableContent(): array
    {
        return [
            'question' => $this->question
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'question_pt' => $translatedData['question']['pt'],
            'question_en' => $translatedData['question']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    protected $casts = [
        'code_snippet' => AsCollection::class,
        'is_multiple_choice' => 'boolean',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function question_responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }

    public function proficiencyTestResponses(): HasMany
    {
        return $this->hasMany(ProficiencyTestResponse::class, 'question_id');
    }
}
