<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    Use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'cep',
        'address_type',
        'street',
        'street_complete_name',
        'district',
        'city',
        'state',
        'latitude',
        'longitude',
        'number',
        'complement'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
