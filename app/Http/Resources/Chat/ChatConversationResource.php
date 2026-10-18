<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatConversationResource extends JsonResource
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
             * O outro participante da conversa, do ponto de vista do usuário autenticado
             */
            'participant' => new ChatParticipantResource($this->otherParticipant($request->user()->id)),
            /**
             * Última mensagem da conversa (`null` enquanto não houver nenhuma)
             */
            'last_message' => new ChatMessageResource($this->whenLoaded('lastMessage')),
            /**
             * Mensagens recebidas pelo usuário autenticado que ele ainda não leu
             */
            'unread_count' => (int) $this->unread_count,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
