<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AcademicBackground extends Model implements HasMedia
{
    use InteractsWithMedia, HasUuidV7, SoftDeletes;

    protected $fillable = [
        'degree',
        'degree_level',
        'institution',
        'dev_profile_id',
    ];

    protected $appends = ['certificate'];
    protected $with = ['media'];

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
