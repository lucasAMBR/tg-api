<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AdditionalCourse extends Model implements HasMedia
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'provider',
        'dev_profile_id'
    ];

    protected $with = ['media'];
    protected $appends = ['certificate'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificate')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function getCertificateAttribute()
    {
        $media = $this->getFirstMedia('certificate');

        if (!$media) {
            return null;
        }

        return [
            'id' => $media->id,
            'url' => $media->getUrl(),
        ];
    }

    public function dev_profile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class);
    }
}
