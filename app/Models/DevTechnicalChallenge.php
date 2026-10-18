<?php

namespace App\Models;

use App\Enums\DevTechnicalChallengeStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Entrega do desafio técnico por uma candidatura. Nasce pendente quando a
 * candidatura entra na etapa de desafio técnico e guarda o que o dev enviou
 */
class DevTechnicalChallenge extends Model implements HasMedia
{
    use HasUuidV7, InteractsWithMedia;

    /**
     * Coleção do projeto compactado enviado pelo dev, aceita só quando o desafio
     * permite o envio do zip
     */
    public const SUBMISSION_COLLECTION = 'submission';

    protected $fillable = [
        'technical_challenge_id',
        'dev_job_vacancy_id',
        'dev_profile_id',
        'status',
        'repository_url',
        'due_date',
        'submitted_at'
    ];

    /**
     * A entrega nasce pendente, aguardando o envio da solução pelo dev
     */
    protected $attributes = [
        'status' => DevTechnicalChallengeStatusEnum::PENDING->value
    ];

    protected $casts = [
        'status' => DevTechnicalChallengeStatusEnum::class,
        'due_date' => 'date',
        'submitted_at' => 'datetime'
    ];

    /**
     * O zip é único: um novo envio substitui o anterior
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::SUBMISSION_COLLECTION)->singleFile();
    }

    /**
     * Zip enviado pelo dev, ou null quando ele não anexou nenhum
     *
     * @return array{id: int, name: string, path: string, url: string, size: int}|null
     */
    public function getSubmissionFileAttribute(): ?array
    {
        $media = $this->getFirstMedia(self::SUBMISSION_COLLECTION);

        if(!$media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'name' => $media->file_name,
            // Caminho do arquivo no disco, relativo à raiz do storage público
            'path' => $media->getPathRelativeToRoot(),
            'url' => $media->getUrl(),
            'size' => (int) $media->size
        ];
    }

    public function technicalChallenge(): BelongsTo
    {
        return $this->belongsTo(TechnicalChallenge::class, 'technical_challenge_id');
    }

    public function devJobVacancy(): BelongsTo
    {
        return $this->belongsTo(DevJobVacancy::class, 'dev_job_vacancy_id');
    }

    public function devProfile(): BelongsTo
    {
        return $this->belongsTo(DevProfile::class, 'dev_profile_id');
    }

    /**
     * Indica se o dev já entregou o desafio
     */
    public function isDelivered(): bool
    {
        return $this->status === DevTechnicalChallengeStatusEnum::DELIVERED;
    }
}
