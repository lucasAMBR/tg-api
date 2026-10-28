<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuário do ranking de popularidade, com o que compõe a pontuação.
 * Espera as relações de User::PROFILE_RELATIONS e o scope withFollowState carregados.
 */
class PopularUserResource extends JsonResource
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
            // Soma dos quatro contadores abaixo.
            'popularity_score' => (int) $this->popularity_score,
            // Recebidos nos posts do usuário, vindos de outras pessoas.
            'received_likes_count' => (int) $this->received_likes_count,
            'received_comments_count' => (int) $this->received_comments_count,
            'received_favorites_count' => (int) $this->received_favorites_count,
            'followers_count' => (int) $this->followers_count,
            // O usuário logado segue este usuário.
            'is_followed' => (bool) $this->is_followed,
            // Este usuário segue o usuário logado.
            'follows_you' => (bool) $this->follows_you,
        ];
    }
}
