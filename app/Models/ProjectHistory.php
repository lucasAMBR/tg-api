<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectHistory extends Model implements HasMedia
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'description',
        'dev_profile_id'
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

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }
}
