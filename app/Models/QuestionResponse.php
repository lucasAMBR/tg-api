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

class QuestionResponse extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'question_id',
        'response',
        'response_en',
        'response_pt',
        'translation_status',
        'is_correct',
        'code_snippet'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'response' => $this->response
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'response_pt' => $translatedData['response']['pt'],
            'response_en' => $translatedData['response']['en'],
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
        'code_snippet' => AsCollection::class
    ];

    public function proficiencyTestResponses(): HasMany
    {
        return $this->hasMany(ProficiencyTestResponse::class, 'question_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
