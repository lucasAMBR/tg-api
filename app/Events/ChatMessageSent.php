<?php

namespace App\Events;

use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Entrega em tempo real uma mensagem nova do chat, pelo canal pessoal
 * (`chat.{userId}`) de cada participante.
 *
 * Usa ShouldBroadcastNow (em vez de ShouldBroadcast) para não passar pela fila:
 * em um chat a mensagem precisa chegar o quanto antes.
 */
class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ChatMessage $message,
        public string $recipientId
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->recipientId}"),
            // O remetente também recebe, para sincronizar as outras abas/dispositivos dele
            new PrivateChannel("chat.{$this->message->sender_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new ChatMessageResource($this->message))->resolve();
    }
}
