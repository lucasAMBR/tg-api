<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProficiencyTestVisualization extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'proficiency_test_id',
        'type',
    ];

    public function proficiencyTest(): BelongsTo
    {
        return $this->belongsTo(ProficiencyTest::class);
    }
}
