<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'is_oficial',
        'is_approved'
    ];

}
