<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploymentHistory extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'company_name',
        'company_location',
        'position_name',
        'employment_type',
        'contract_type',
        'seniority_level',
        'actuation_details',
        'start_date',
        'end_date',
        'is_current',
        'dev_profile_id'
    ];

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
