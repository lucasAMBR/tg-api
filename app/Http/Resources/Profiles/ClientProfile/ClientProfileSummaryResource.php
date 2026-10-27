<?php

namespace App\Http\Resources\Profiles\ClientProfile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil público do cliente, exibido para outros usuários (ex.: o dono de um freela).
 * Diferente do `ClientProfileResource`, não leva CPF, telefone nem data de nascimento.
 * Carregue a relação `user` junto para a foto não gerar N+1.
 */
class ClientProfileSummaryResource extends JsonResource
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
            /**
             * Id do usuário (é o usado em `POST /chat` para abrir a conversa)
             */
            'user_id' => $this->user_id,
            'name' => $this->name,
            'profile_pic' => $this->user?->profile_pic['original_url'] ?? null,
            'bio' => $this->bio,
            'bio_pt' => $this->bio_pt,
            'bio_en' => $this->bio_en,
            'score' => $this->score,
        ];
    }
}
