<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProficiencyTest extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'dev_profile_id',
        'seniority_level',
        'specialty',
        'backend_category',
        'frontend_category',
        'status',
        'solicitation_date',
        'score',
        'max_score',
    ];

    protected $casts = [
        'solicitation_date' => 'datetime',
    ];

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }

    public function proficiencyTestResponses(): HasMany
    {
        return $this->hasMany(ProficiencyTestResponse::class);
    }

    public function proficiencyTestVisualizations(): HasMany
    {
        return $this->hasMany(ProficiencyTestVisualization::class);
    }
}
