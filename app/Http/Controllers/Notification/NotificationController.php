<?php

namespace App\Http\Controllers\Notification;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Models\Notification;
use App\Services\Notification\NotificationService;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(IndexNotificationRequest $request)
    {
        $notifications = $this->notificationService->index($request->validated());

        return ApiResponse::success($notifications, 'Notifications listed with success!');
    }

    public function markAsRead(Notification $notification)
    {
        $notification = $this->notificationService->markAsRead($notification);

        return ApiResponse::success($notification, 'Notification marked as read with success!');
    }

    public function markAllAsRead()
    {
        $notifications = $this->notificationService->markAllAsRead();

        return ApiResponse::success($notifications, 'All notifications marked as read with success!');
    }
}
