<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{

    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_official',
        'is_approved'
    ];

    public function project_histories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectHistory::class);
    }

    public function company_projects(): BelongsToMany {
        return $this->belongsToMany(CompanyProject::class, 'language_company_project');
    }

}   
