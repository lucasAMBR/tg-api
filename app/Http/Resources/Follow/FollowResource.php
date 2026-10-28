<?php

namespace App\Http\Resources\Follow;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuário de uma lista de seguidores ou de seguidos.
 * Espera as relações de User::PROFILE_RELATIONS e o scope withFollowState carregados.
 */
class FollowResource extends JsonResource
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
            // O usuário logado segue este usuário.
            'is_followed' => (bool) $this->is_followed,
            // Este usuário segue o usuário logado.
            'follows_you' => (bool) $this->follows_you,
            'followed_at' => $this->pivot?->created_at?->toIso8601String(),
        ];
    }
}
