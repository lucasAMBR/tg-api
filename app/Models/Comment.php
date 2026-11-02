<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasLikes;
use App\Traits\HasUuidV7;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model implements Translatable
{
    use HasUuidV7, HasLikes;

    public const REMOVED_CONTENT = '[removed]';

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'content_pt',
        'content_en',
        'translation_status',
        'original_content',
        'removed_at',
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value,
    ];

    protected $hidden = [
        'original_content',
    ];

    protected $casts = [
        'removed_at' => 'datetime',
    ];

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    /**
     * Formato da remoção lógica, usado pelo CommentService e pelo PostSeeder: a linha fica para as
     * respostas continuarem visíveis, o original é preservado e o texto vira o marcador em todos os
     * idiomas, então não sobra nada para traduzir.
     */
    public function removalAttributes(CarbonInterface $removedAt): array
    {
        return [
            'original_content' => $this->content,
            'content' => self::REMOVED_CONTENT,
            'content_pt' => self::REMOVED_CONTENT,
            'content_en' => self::REMOVED_CONTENT,
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
            'removed_at' => $removedAt,
        ];
    }

    public function getTranslatableContent(): array
    {
        return [
            'content' => $this->content,
        ];
    }

    /**
     * Sem mexer no updated_at: a tradução chegar não é uma edição do comentário.
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
