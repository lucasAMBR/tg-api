<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftSkillLevelResponse extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'soft_skill_id',
        'title',
        'description',
        'evaluation_weight'
    ];

    public function soft_skill(): BelongsTo
    {
        return $this->belongsTo(SoftSkill::class);
    }

    public function dev_soft_skills(): HasMany
    {
        return $this->hasMany(DevSoftSkill::class);
    }
}
