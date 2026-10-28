<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Repassa um sinal WebRTC (offer, answer ou ICE candidate) para o outro
 * participante da entrevista. É puramente um relay: o conteúdo da chamada
 * em si (áudio/vídeo) nunca passa pelo backend, só essa etapa de sinalização.
 *
 * Usa ShouldBroadcastNow (em vez de ShouldBroadcast) para não passar pela fila:
 * sinalização é sensível a latência e precisa ser entregue o quanto antes.
 */
class InterviewSignalReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $interviewId,
        public string $from,
        public string $type,
        public mixed $payload
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("interview.{$this->interviewId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'signal';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            // Quem mandou o sinal ('dev' ou 'company'), para o outro lado
            // reconhecer e o próprio remetente ignorar o próprio eco
            'from' => $this->from,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
