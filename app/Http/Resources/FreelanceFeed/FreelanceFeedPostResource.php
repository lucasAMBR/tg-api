<?php

namespace App\Http\Resources\FreelanceFeed;

use App\Enums\FreelanceFeedPostTypeEnum;
use App\Http\Resources\User\UserSimpleResource;
use App\Models\FreelanceFeedPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FreelanceFeedPostResource extends JsonResource
{
    /**
     * Por quanto tempo a URL de um anexo funciona depois de o feed ser carregado
     */
    private const ATTACHMENT_URL_TTL_MINUTES = 30;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'freelance_job_vacancy_id' => $this->freelance_job_vacancy_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'content' => $this->content,
            /**
             * `null` quando a conta do autor foi excluída
             */
            'author' => $this->whenLoaded('user', fn() => $this->user ? new UserSimpleResource($this->user) : null),
            /**
             * A `url` é assinada e expira em 30 minutos: os anexos ficam no disco privado.
             * Para baixar de novo depois disso, recarregue o feed
             */
            'attachments' => $this->getMedia(FreelanceFeedPost::ATTACHMENTS_COLLECTION)->map(fn(Media $media) => [
                'id' => (int) $media->id,
                'name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => (int) $media->size,
                'url' => $media->getTemporaryUrl(now()->addMinutes(self::ATTACHMENT_URL_TTL_MINUTES)),
            ])->values(),
            /**
             * Na review, o id da entrega revisada
             */
            'parent_id' => $this->parent_id,
            'review_status' => $this->review_status?->value,
            'review_status_label' => $this->review_status?->label(),
            /**
             * Na entrega, a review mais recente do cliente (`null` enquanto não foi revisada)
             */
            'latest_review' => $this->when(
                $this->type === FreelanceFeedPostTypeEnum::DELIVERABLE,
                fn() => $this->whenLoaded('latestReview', fn() => $this->latestReview ? [
                    'id' => $this->latestReview->id,
                    'review_status' => $this->latestReview->review_status?->value,
                    'review_status_label' => $this->latestReview->review_status?->label(),
                    'created_at' => $this->latestReview->created_at?->toIso8601String(),
                ] : null)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
