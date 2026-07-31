<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevProfileEmbedding extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'dev_profile_id',
        'embedding'
    ];

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
