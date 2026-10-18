<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Enums\TranslationStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Desafio técnico da vaga, entregue pelos candidatos na etapa `technical_challenge`
 * do processo seletivo
 */
class TechnicalChallenge extends Model implements HasMedia, Translatable
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia;

    /**
     * Coleção do documento guia do desafio, o arquivo com o enunciado completo
     */
    public const GUIDE_DOCUMENT_COLLECTION = 'guide_document';

    protected $fillable = [
        'job_vacancy_id',
        'title',
        'title_pt',
        'title_en',
        'description',
        'description_pt',
        'description_en',
        'rules',
        'rules_pt',
        'rules_en',
        'translation_status',
        'due_date',
        'accepts_zip_submission'
    ];

    protected $attributes = [
        'translation_status' => TranslationStatusEnum::PENDING->value
    ];

    protected $casts = [
        'due_date' => 'date',
        'accepts_zip_submission' => 'boolean'
    ];

    /**
     * O documento guia é único: um novo envio substitui o anterior
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::GUIDE_DOCUMENT_COLLECTION)->singleFile();
    }

    public function getTranslatableContent(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'rules' => $this->rules ?? ''
        ];
    }

    public function applyTranslation(array $translatedData): void
    {
        $this->update([
            'title_pt' => $translatedData['title']['pt'],
            'title_en' => $translatedData['title']['en'],
            'description_pt' => $translatedData['description']['pt'],
            'description_en' => $translatedData['description']['en'],
            'rules_pt' => $translatedData['rules']['pt'],
            'rules_en' => $translatedData['rules']['en'],
            'translation_status' => TranslationStatusEnum::TRANSLATED->value
        ]);
    }

    public function updateTranslationStatus(string $status): void
    {
        $this->update([
            'translation_status' => $status
        ]);
    }

    /**
     * Documento guia do desafio, ou null quando a empresa não anexou nenhum
     *
     * @return array{id: int, name: string, path: string, url: string, size: int}|null
     */
    public function getGuideDocumentAttribute(): ?array
    {
        $media = $this->getFirstMedia(self::GUIDE_DOCUMENT_COLLECTION);

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

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    /**
     * Entregas do desafio, uma por candidatura que chegou à etapa
     */
    public function devChallenges(): HasMany
    {
        return $this->hasMany(DevTechnicalChallenge::class, 'technical_challenge_id');
    }
}
