<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientProfile extends Model implements Translatable
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'bio_pt',
        'bio_en',
        'translation_status',
        'phone',
        'cpf',
        'birthdate',
        'score'
    ];

    public function getTranslatableContent(): array
    {
        return [
            'bio' => $this->bio
        ];
    }

    public function applyTranslation(array $translatedData):void
    {
        $this->update([
            'bio_pt' => $translatedData['bio']['pt'],
            'bio_en' => $translatedData['bio']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value,
        ]);
    }

    public function updateTranslationStatus(string $status):void
    {
        $this->update([
            'translation_status' => $status,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
