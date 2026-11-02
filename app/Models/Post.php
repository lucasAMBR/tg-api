<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasLikes;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia, Translatable
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia, HasLikes;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'content',
        'content_pt',
        'content_en',
        'translation_status',
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(600)
            ->format('webp')
            ->quality(80);
    }

    public function getTranslatableContent(): array
    {
        return [
            'content' => $this->content,
        ];
    }

    /**
     * Sem mexer no updated_at: o front mostra "editado" quando ele difere do created_at,
     * e a tradução chegar não é uma edição.
     */
    public function applyTranslation(array $translatedData): void
    {
        static::withoutTimestamps(fn () => $this->update([
            'content_pt' => $translatedData['content']['pt'],
            'content_en' => $translatedData['content']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]));
    }

    public function updateTranslationStatus(string $status): void
    {
        static::withoutTimestamps(fn () => $this->update([
            'translation_status' => $status,
        ]));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_posts')->withTimestamps();
    }
}
