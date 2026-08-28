<?php

namespace App\Models;

use App\Casts\Vector;
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

    protected $casts = [
        'embedding' => Vector::class,
    ];

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
