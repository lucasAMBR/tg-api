<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionResponse extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'question_id',
        'response',
        'is_correct',
        'code_snippet'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
