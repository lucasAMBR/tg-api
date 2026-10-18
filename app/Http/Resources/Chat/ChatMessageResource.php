<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            /**
             * Quando o destinatário leu a mensagem. Para quem enviou, só vem preenchido
             * quando a confirmação de leitura está ligada para os dois participantes
             */
            'read_at' => $this->readAtFor($request->user()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
