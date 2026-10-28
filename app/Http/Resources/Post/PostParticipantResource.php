<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuário que participa da conversa nos comentários de uma postagem.
 * Espera as relações de User::PROFILE_RELATIONS, o scope withFollowState e as colunas
 * de participação calculadas em PostService::participants carregados.
 */
class PostParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserSimpleResource($this->resource),
            // Comentários e respostas do usuário nesta postagem, sem os removidos.
            'comments_count' => (int) $this->comments_count,
            // O usuário é o autor da postagem e entrou na conversa dos comentários.
            'is_post_author' => (bool) $this->is_post_author,
            'first_commented_at' => $this->first_commented_at?->toIso8601String(),
            'last_commented_at' => $this->last_commented_at?->toIso8601String(),
            // O usuário logado segue este usuário.
            'is_followed' => (bool) $this->is_followed,
            // Este usuário segue o usuário logado.
            'follows_you' => (bool) $this->follows_you,
        ];
    }
}
