<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanySoftSkill extends Model
{

    use HasUuidV7, SoftDeletes;
     
    // Passo a tabela relacionada ao model
    protected $table = 'company_soft_skills'; 

    protected $fillable = [
        'soft_skill_id',
        'company_profile_id'
    ];

    // Passo as relações com tabelas 
    protected $with = [
        'soft_skills',
    ];

    public function soft_skills(): BelongsTo {
        return $this->belongsTo(SoftSkill::class, 'soft_skill_id');
    }

    public function company_profile(): BelongsTo {
        return $this->belongsTo(CompanyProfile::class);
    }
}

