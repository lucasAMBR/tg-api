<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftSkill extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'name',
        'description'
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SoftSkillLevelResponse::class);
    }

    public function dev_soft_skill(): HasMany
    {
        return $this->hasMany(DevSoftSkill::class);
    }
}
