<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploymentHistory extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'company_name',
        'company_location',
        'position_name',
        'position_name_pt',
        'position_name_en',
        'employment_type',
        'contract_type',
        'seniority_level',
        'actuation_details',
        'actuation_details_pt',
        'actuation_details_en',
        'translation_status',
        'start_date',
        'end_date',
        'is_current',
        'dev_profile_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function getTranslatableContent(): array
    {
        return [
            'position_name' => $this->position_name,
            'actuation_details' => $this->actuation_details
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'position_name_pt' => $translatedData['position_name']['pt'],
            'position_name_en' => $translatedData['position_name']['en'],
            'actuation_details_pt' => $translatedData['actuation_details']['pt'],
            'actuation_details_en' => $translatedData['actuation_details']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
