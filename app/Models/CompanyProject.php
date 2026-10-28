<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyProject extends Model implements Translatable
{
    
    use SoftDeletes, HasUuidV7;

    protected $fillable = [
        'title',
        'title_pt',
        'title_en',
        'description',
        'description_pt',
        'description_en',
        'translation_status',
        'company_profile_id',
        'prod_url',
        'github_url',
    ];

    /**
     * Sempre carrega os relacionamentos passados aqui
     * sem necessidade de usar o método load() ou with()
     */
    public $with = [
        'languages'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'title_pt' => $translatedData['title']['pt'],
            'title_en' => $translatedData['title']['en'],
            'description_pt' => $translatedData['description']['pt'],
            'description_en' => $translatedData['description']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    public function company_profile(): BelongsTo {
        return $this->belongsTo(CompanyProfile::class);
    } 

    public function languages(): BelongsToMany {
        return $this->belongsToMany(Language::class, 'language_company_project');
    }

}
