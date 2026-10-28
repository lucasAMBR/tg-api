<?php

namespace App\Http\Controllers\Notification;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Requests\Notification\MarkAsReadNotificationRequest;
use App\Services\Notification\NotificationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    #[Endpoint(operationId: 'indexNotification', title: 'Listar notificações', description: '**operationId:** `indexNotification` — Lista paginada das notificações do perfil ativo do usuário autenticado, ordenada da mais recente para a mais antiga, com filtro opcional `filter` (`all`, `unread` ou `read`). Em **200**, `data.data[]` segue o schema **Notification Resource** (`App\\Http\\Resources\\Notification\\NotificationResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        try {
            $notifications = $this->notificationService->index($request->validated());

            return ApiResponse::success($notifications, 'Notifications listed with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'markNotificationAsRead', title: 'Marcar notificação como lida', description: '**operationId:** `markNotificationAsRead` — Preenche o `read_at` da notificação informada (operação idempotente: se já estiver lida, nada muda). A notificação precisa pertencer ao perfil ativo do usuário autenticado, caso contrário a resposta é **403**. Em **200**, `data` segue o schema **Notification Resource** (`App\\Http\\Resources\\Notification\\NotificationResource`).')]
    public function markAsRead(MarkAsReadNotificationRequest $request): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead($request->validated());

            return ApiResponse::success($notification, 'Notification marked as read with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'markAllNotificationsAsRead', title: 'Marcar todas as notificações como lidas', description: '**operationId:** `markAllNotificationsAsRead` — Preenche o `read_at` de todas as notificações não lidas do perfil ativo do usuário autenticado e devolve a listagem atualizada. Em **200**, `data.data[]` segue o schema **Notification Resource** (`App\\Http\\Resources\\Notification\\NotificationResource`) e `data.pagination` traz os metadados de paginação.')]
    public function markAllAsRead(): JsonResponse
    {
        try {
            $notifications = $this->notificationService->markAllAsRead();

            return ApiResponse::success($notifications, 'All notifications marked as read with success!');
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
