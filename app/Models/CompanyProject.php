<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyProject extends Model
{
    
    use SoftDeletes, HasUuidV7;

    protected $fillable = [
        'title',
        'description', 
        'company_profile_id',
        'languages'
    ];

    public function company_profile(): BelongsTo {
        return $this->belongsTo(CompanyProfile::class);
    } 

    public function languages(): BelongsToMany {
        return $this->belongsToMany(Language::class, 'language_company_project');
    }

}
