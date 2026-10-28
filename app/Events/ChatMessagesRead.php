<?php

namespace App\Events;

use Carbon\CarbonInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Avisa em tempo real que as mensagens recebidas em uma conversa foram lidas.
 *
 * Sempre vai para o canal de quem leu (para zerar as não lidas nas outras
 * abas/dispositivos dele), mas só vai para o canal de quem enviou quando a
 * confirmação de leitura está ligada para os dois.
 *
 * Usa ShouldBroadcastNow (em vez de ShouldBroadcast) para não passar pela fila.
 */
class ChatMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $conversationId,
        public string $readerId,
        public string $senderId,
        public CarbonInterface $readAt,
        public bool $notifySender
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("chat.{$this->readerId}")];

        if ($this->notifySender) {
            $channels[] = new PrivateChannel("chat.{$this->senderId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            // Toda mensagem do remetente nessa conversa criada até esse momento está lida
            'read_at' => $this->readAt,
        ];
    }
}
