<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuidV7;

    protected $fillable = ['name'];
}
