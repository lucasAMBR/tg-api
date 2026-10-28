<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProficiencyTestResponse extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'proficiency_test_id',
        'question_id',
        'question_response_id',
        'time_taken',
        'alt_tabs_used',
        'status',
    ];

    public function proficiencyTest(): BelongsTo
    {
        return $this->belongsTo(ProficiencyTest::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function questionResponse(): BelongsTo
    {
        return $this->belongsTo(QuestionResponse::class);
    }
}
