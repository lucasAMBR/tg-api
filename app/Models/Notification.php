<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Observers\NotificationObserver;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(NotificationObserver::class)]
class Notification extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'title_pt',
        'title_en',
        'message',
        'message_pt',
        'message_en',
        'translation_status',
        'read_at',
        'link',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTranslatableContent(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }

    public function applyTranslation(array $translatedData): void
    {
        $this->update([
            'title_pt' => $translatedData['title']['pt'],
            'title_en' => $translatedData['title']['en'],
            'message_pt' => $translatedData['message']['pt'],
            'message_en' => $translatedData['message']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status): void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    public function scopeUnread(Builder $query)
    {
        return $query->whereNull('read_at');
    }
}
