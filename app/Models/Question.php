<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        "question",
        "difficulty_level",
        "language_id",
        "category",
        "ideal_time_to_solve",
        "code_snippet",
        "is_multiple_choice",
        "seniority_level"
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function question_responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
