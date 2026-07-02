<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectHistory extends Model implements HasMedia, Translatable
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'title_pt',
        'title_en',
        'description',
        'description_pt',
        'description_en',
        'translation_status',
        'dev_profile_id',
        'prod_url',
        'github_url',
    ];

    protected $appends = ['gallery'];

    protected $with = ['media'];

    // ============================= GALERY ==================================

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(533)
            ->height(300)
            ->format('webp')
            ->quality(80);
    }

    public function getGalleryAttribute()
    {
        return $this->getMedia('gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl()
            ];
        });
    }

    // ============================= RELATIONSHIPS ==================================

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

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }
}
