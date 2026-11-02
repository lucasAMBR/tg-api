<?php

namespace App\Http\Resources\Comment;

use App\Enums\TranslationStatusEnum;
use App\Http\Resources\User\UserSimpleResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            // Removido não expõe o texto original em nenhum idioma, nem quando a
            // tradução que já estava na fila termina depois da remoção.
            'content_pt' => $this->isRemoved() ? Comment::REMOVED_CONTENT : $this->content_pt,
            'content_en' => $this->isRemoved() ? Comment::REMOVED_CONTENT : $this->content_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'is_removed' => $this->isRemoved(),
            'removed_at' => $this->removed_at?->toIso8601String(),
            // Comentário removido não expõe mais quem escreveu.
            'author' => $this->isRemoved() ? null : $this->whenLoaded('user', function () {
                return new UserSimpleResource($this->user);
            }),
            'likes_count' => (int) $this->likes_count,
            'is_liked' => (bool) $this->is_liked,
            // Total real de respostas diretas, não só as que vieram no preview.
            'replies_count' => (int) $this->replies_count,
            // Indica ao front se deve exibir "ver mais respostas" / "continuar esta thread".
            'has_more_replies' => $this->whenLoaded('replies', function () {
                return (int) $this->replies_count > $this->replies->count();
            }),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
