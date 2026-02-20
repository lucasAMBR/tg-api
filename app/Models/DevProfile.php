<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevProfile extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'bio',
        'cpf',
        'birthdate',
        'seniority_level',
        'score'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
