<?php

namespace App\Models;

use App\Enums\FreelanceFeedPostTypeEnum;
use App\Enums\FreelanceReviewStatusEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Postagem no feed de um freela contratado, separada do chat: atualizações, entregas
 * do dev e reviews do cliente, com documentos anexados
 */
class FreelanceFeedPost extends Model implements HasMedia
{
    use HasUuidV7, SoftDeletes, InteractsWithMedia;

    public const ATTACHMENTS_COLLECTION = 'attachments';

    protected $fillable = [
        'freelance_job_vacancy_id',
        'user_id',
        'type',
        'content',
        'parent_id',
        'review_status'
    ];

    protected function casts(): array
    {
        return [
            'type' => FreelanceFeedPostTypeEnum::class,
            'review_status' => FreelanceReviewStatusEnum::class
        ];
    }

    /**
     * Os anexos são documentos do trabalho contratado, então ficam no disco privado e
     * só saem por URL temporária assinada, entregue a quem pode ver o feed
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ATTACHMENTS_COLLECTION)->useDisk('local');
    }

    public function freelanceJobVacancy(): BelongsTo
    {
        return $this->belongsTo(FreelanceJobVacancy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Na review, a entrega revisada
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Review mais recente da entrega, que define se ela está aprovada ou com ajustes pendentes
     */
    public function latestReview(): HasOne
    {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid. O `created_at` tem precisão de segundo, então duas
        // reviews no mesmo segundo desempatam pelo id (UUIDv7, em ordem de criação),
        // como no feed
        return $this->hasOne(self::class, 'parent_id')
            ->latest('created_at')
            ->orderByDesc('id');
    }
}
