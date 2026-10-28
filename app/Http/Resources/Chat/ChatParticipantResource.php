<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Participante de uma conversa do chat (um `User`), com o nome e o id do perfil
 * correspondente à role dele
 */
class ChatParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->roles->first()?->name;

        $profile = match ($role) {
            'dev' => $this->dev_profile,
            'company' => $this->company_profile,
            'client' => $this->client_profile,
            'admin' => $this->admin_profile,
            default => null,
        };

        return [
            /**
             * Id do usuário (é o usado em `POST /chat` para abrir a conversa)
             */
            'id' => $this->id,
            'role' => $role,
            /**
             * Id do perfil correspondente à role (`null` se o usuário ainda não criou o perfil)
             */
            'profile_id' => $profile?->id,
            'name' => $profile?->name,
            'profile_pic' => $this->profile_pic,
        ];
    }
}
