<?php

namespace App\Services\Notification;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\Notification\NotificationCollection;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $filter = $data['filter'] ?? 'all';

        $profile = ProfileHelper::getUserProfileByRole(Auth::user());

        $notifications = Notification::query()
            ->where('notifiable_type', $profile->getMorphClass())
            ->where('notifiable_id', $profile->id)
            ->when($filter === 'unread', fn (Builder $q) => $q->whereNull('read_at'))
            ->when($filter === 'read', fn (Builder $q) => $q->whereNotNull('read_at'))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationCollection($notifications);
    }

    public function markAsRead(array $data)
    {
        $notification = Notification::findOrFail($data['id']);

        $this->ensureOwnership($notification);

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return new NotificationResource($notification->fresh());
    }

    public function markAllAsRead()
    {
        $profile = ProfileHelper::getUserProfileByRole(Auth::user());

        Notification::query()
            ->where('notifiable_type', $profile->getMorphClass())
            ->where('notifiable_id', $profile->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return $this->index([]);
    }

    private function ensureOwnership(Notification $notification): void
    {
        $profile = ProfileHelper::getUserProfileByRole(Auth::user());

        $belongsToProfile = $notification->notifiable_type === $profile->getMorphClass()
            && $notification->notifiable_id === $profile->id;

        if (! $belongsToProfile) {
            throw new ApiException('This notification does not belong to your profile!', 403);
        }
    }
}
