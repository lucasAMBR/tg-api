<?php

namespace App\Services\Chat;

use App\Events\ChatMessageSent;
use App\Events\ChatMessagesRead;
use App\Exceptions\ApiException;
use App\Http\Resources\Chat\ChatConversationCollection;
use App\Http\Resources\Chat\ChatConversationResource;
use App\Http\Resources\Chat\ChatMessageCollection;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Relações do usuário usadas para montar o participante da conversa: a role
     * define de qual perfil vêm o nome e o id
     */
    private const PARTICIPANT_RELATIONS = ['roles', 'dev_profile', 'company_profile', 'client_profile', 'admin_profile'];

    /**
     * Lista as conversas do usuário autenticado, da mais recente para a mais antiga.
     * Conversas abertas mas ainda sem nenhuma mensagem ficam de fora
     */
    public function index(array $data): ChatConversationCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 20;

        $authUserId = Auth::id();

        $conversations = ChatConversation::query()
            ->ofParticipant($authUserId)
            ->whereNotNull('last_message_at')
            ->with($this->relations())
            ->withCount($this->unreadCount($authUserId))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ChatConversationCollection($conversations);
    }

    /**
     * Abre a conversa com outro usuário, criando-a se ainda não existir. É por aqui
     * que o front obtém o id da conversa antes de mandar a primeira mensagem
     */
    public function open(array $data): ChatConversationResource
    {
        $authUserId = Auth::id();

        $otherUser = User::findOrFail($data['user_id']);

        if ($otherUser->id === $authUserId) {
            throw new ApiException("You can't start a conversation with yourself!", 400);
        }

        $conversation = $this->firstOrCreateConversation($authUserId, $otherUser->id);

        return $this->toResource($conversation);
    }

    public function show(array $data): ChatConversationResource
    {
        $conversation = $this->getParticipantConversation($data['id']);

        return $this->toResource($conversation);
    }

    /**
     * Lista as mensagens da conversa, da mais recente para a mais antiga
     */
    public function indexMessages(array $data): ChatMessageCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 30;

        $conversation = $this->getParticipantConversation($data['id']);

        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ChatMessageCollection($messages);
    }

    public function storeMessage(array $data): ChatMessageResource
    {
        $conversation = $this->getParticipantConversation($data['id']);

        $authUserId = Auth::id();

        $message = DB::transaction(function () use ($conversation, $authUserId, $data) {

            $message = $conversation->messages()->create([
                'sender_id' => $authUserId,
                'body' => $data['body'],
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;

        });

        $recipientId = $conversation->otherParticipantId($authUserId);

        // O tempo real é complementar: se o Reverb estiver fora do ar a mensagem já está
        // salva e chega na próxima listagem, então a falha só vai para o log
        rescue(function () use ($message, $recipientId) {
            ChatMessageSent::broadcast($message, $recipientId)->toOthers();
        });

        return new ChatMessageResource($message);
    }

    /**
     * Envia uma mensagem gerada pelo sistema em nome de um usuário (ex.: a empresa
     * avisando o dev aprovado no fim do processo seletivo), abrindo a conversa entre
     * os dois se ainda não existir. Pode ser chamada dentro de uma transação: o envio
     * em tempo real só acontece depois do commit
     */
    public function sendAutomaticMessage(string $senderId, string $recipientId, string $body): ChatMessage
    {
        $conversation = $this->firstOrCreateConversation($senderId, $recipientId);

        $message = $conversation->messages()->create([
            'sender_id' => $senderId,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        // Sem o toOthers(): a mensagem não saiu de nenhuma aba do remetente, então
        // todas as abas dele também precisam recebê-la
        DB::afterCommit(fn () => rescue(function () use ($message, $recipientId) {
            ChatMessageSent::broadcast($message, $recipientId);
        }));

        return $message;
    }

    /**
     * Marca como lidas as mensagens recebidas na conversa. O front chama sempre que a
     * conversa está aberta na tela: ao abrir e a cada mensagem nova que chegar nela
     */
    public function markAsRead(array $data): ChatConversationResource
    {
        $conversation = $this->getParticipantConversation($data['id']);

        /** @var User $reader */
        $reader = Auth::user();
        $sender = $conversation->otherParticipant($reader->id);
        $readAt = now();

        $received = $conversation->messages()->where('sender_id', $sender->id);

        if ($reader->read_receipts_enabled) {
            // Com a confirmação ligada, a leitura confirma tudo o que já foi recebido,
            // inclusive o que foi lido enquanto ela estava desligada. Essas ficam com o
            // horário de agora, para não revelar quando foram lidas de fato
            $readCount = $received
                ->where(fn (Builder $query) => $query->whereNull('read_at')->orWhere('read_receipt_hidden', true))
                ->update(['read_at' => $readAt, 'read_receipt_hidden' => false]);
        } else {
            $readCount = $received
                ->whereNull('read_at')
                ->update(['read_at' => $readAt, 'read_receipt_hidden' => true]);
        }

        if ($readCount > 0) {
            $notifySender = $reader->read_receipts_enabled && $sender->read_receipts_enabled;

            rescue(function () use ($conversation, $reader, $sender, $readAt, $notifySender) {
                ChatMessagesRead::broadcast($conversation->id, $reader->id, $sender->id, $readAt, $notifySender)->toOthers();
            });
        }

        return $this->toResource($conversation);
    }

    private function firstOrCreateConversation(string $userId, string $otherUserId): ChatConversation
    {
        // O par é sempre gravado na mesma ordem, para o índice único impedir duas
        // conversas entre os mesmos usuários
        [$userOneId, $userTwoId] = collect([$userId, $otherUserId])->sort()->values()->all();

        return ChatConversation::firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    private function toResource(ChatConversation $conversation): ChatConversationResource
    {
        $conversation->load($this->relations())
            ->loadCount($this->unreadCount(Auth::id()));

        return new ChatConversationResource($conversation);
    }

    /**
     * Participantes (com o perfil, para o nome e a foto) e a última mensagem
     */
    private function relations(): array
    {
        $participant = fn (BelongsTo $query) => $query->with(self::PARTICIPANT_RELATIONS);

        return [
            'userOne' => $participant,
            'userTwo' => $participant,
            'lastMessage',
        ];
    }

    /**
     * Quantidade de mensagens recebidas pelo usuário que ele ainda não leu
     */
    private function unreadCount(string $userId): array
    {
        return [
            'messages as unread_count' => fn (Builder $query) => $query
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at'),
        ];
    }

    /**
     * Garante que a conversa pertence ao usuário autenticado
     */
    private function getParticipantConversation(string $id): ChatConversation
    {
        $conversation = ChatConversation::query()
            ->ofParticipant(Auth::id())
            ->find($id);

        if (!$conversation) {
            throw new ApiException("This conversation does not belong to you!", 403);
        }

        return $conversation;
    }
}
