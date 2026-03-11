<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevSoftSkill extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $table = 'dev_soft_skill';

    protected $fillable = [
        'soft_skill_id',
        'soft_skill_level_response_id',
        'dev_profile_id'
    ];

    protected $with = [
        'soft_skill',
        'soft_skill_level_response'
    ];

    public function soft_skill(): BelongsTo
    {
        return $this->belongsTo(SoftSkill::class);
    }

    public function soft_skill_level_response(): BelongsTo
    {
        return $this->belongsTo(SoftSkillLevelResponse::class);
    }

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
