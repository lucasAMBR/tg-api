<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HardSkill extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'language_id',
        'skill_level',
        'dev_profile_id'
    ];

    public $with = ['language'];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
