<?php

namespace App\Http\Controllers\Chat;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\IndexChatConversationRequest;
use App\Http\Requests\Chat\IndexChatMessageRequest;
use App\Http\Requests\Chat\MarkAsReadChatConversationRequest;
use App\Http\Requests\Chat\ShowChatConversationRequest;
use App\Http\Requests\Chat\StoreChatConversationRequest;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Services\Chat\ChatService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService) {}

    #[Endpoint(operationId: 'indexChatConversation', title: 'Listar conversas do chat', description: '**operationId:** `indexChatConversation` — Lista paginada das conversas do usuário autenticado, da que recebeu mensagem mais recentemente para a mais antiga. Conversas abertas mas ainda sem nenhuma mensagem não aparecem. Em **200**, `data.data[]` segue o schema **Chat Conversation Resource** (`App\\Http\\Resources\\Chat\\ChatConversationResource`) — com o outro participante, a última mensagem e a quantidade de não lidas — e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexChatConversationRequest $request): JsonResponse
    {
        try {
            $conversations = $this->chatService->index($request->validated());

            return ApiResponse::success($conversations, 'Conversations listed with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'openChatConversation', title: 'Abrir conversa do chat', description: '**operationId:** `openChatConversation` — Abre a conversa 1:1 com o usuário `user_id` (de qualquer tipo de perfil), criando-a se ainda não existir: chamar de novo com o mesmo usuário devolve a mesma conversa, seja quem for que a abriu. Não é possível abrir uma conversa consigo mesmo (**400**). Em **200**, `data` segue o schema **Chat Conversation Resource** (`App\\Http\\Resources\\Chat\\ChatConversationResource`).')]
    public function store(StoreChatConversationRequest $request): JsonResponse
    {
        try {
            $conversation = $this->chatService->open($request->validated());

            return ApiResponse::success($conversation, 'Conversation opened with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showChatConversation', title: 'Consultar conversa do chat', description: '**operationId:** `showChatConversation` — Retorna uma conversa do usuário autenticado (**403** se ele não for participante). Útil quando chega pelo canal `chat.{userId}` uma mensagem de uma conversa que ainda não está na lista do front. Em **200**, `data` segue o schema **Chat Conversation Resource** (`App\\Http\\Resources\\Chat\\ChatConversationResource`).')]
    public function show(ShowChatConversationRequest $request): JsonResponse
    {
        try {
            $conversation = $this->chatService->show($request->validated());

            return ApiResponse::success($conversation, 'Conversation found with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'markChatConversationAsRead', title: 'Marcar conversa do chat como lida', description: '**operationId:** `markChatConversationAsRead` — Marca como lidas todas as mensagens recebidas na conversa (**403** se o usuário autenticado não for participante). Com `read_receipts_enabled` ligado para os dois participantes, o remetente é avisado em tempo real pelo evento `messages.read`; caso contrário, a leitura só é registrada para a contagem de não lidas. Em **200**, `data` segue o schema **Chat Conversation Resource** (`App\\Http\\Resources\\Chat\\ChatConversationResource`), já com `unread_count` zerado.')]
    public function markAsRead(MarkAsReadChatConversationRequest $request): JsonResponse
    {
        try {
            $conversation = $this->chatService->markAsRead($request->validated());

            return ApiResponse::success($conversation, 'Conversation marked as read with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexChatMessage', title: 'Listar mensagens da conversa', description: '**operationId:** `indexChatMessage` — Lista paginada das mensagens da conversa, da mais recente para a mais antiga (**403** se o usuário autenticado não for participante). O `read_at` das mensagens enviadas pelo usuário autenticado só vem preenchido quando a confirmação de leitura está ligada para os dois participantes. Em **200**, `data.data[]` segue o schema **Chat Message Resource** (`App\\Http\\Resources\\Chat\\ChatMessageResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexMessages(IndexChatMessageRequest $request): JsonResponse
    {
        try {
            $messages = $this->chatService->indexMessages($request->validated());

            return ApiResponse::success($messages, 'Messages listed with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeChatMessage', title: 'Enviar mensagem no chat', description: '**operationId:** `storeChatMessage` — Envia uma mensagem de texto (`body`, máx. 2000 caracteres) na conversa (**403** se o usuário autenticado não for participante). A mensagem é entregue em tempo real pelo evento `message.sent` no canal `chat.{userId}` dos dois participantes; mande o header `X-Socket-ID` para a aba que enviou não receber o próprio eco. Em **201**, `data` segue o schema **Chat Message Resource** (`App\\Http\\Resources\\Chat\\ChatMessageResource`).')]
    public function storeMessage(StoreChatMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->chatService->storeMessage($request->validated());

            return ApiResponse::success($message, 'Message sent with success!', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
