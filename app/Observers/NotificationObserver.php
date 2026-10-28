<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Jobs\TranslateContentJob;
use App\Models\Notification;
use Illuminate\Support\Facades\Bus;

class NotificationObserver
{
    /**
     * Handle the Notification "created" event.
     */
    public function created(Notification $notification): void
    {
        Bus::chain([
            new TranslateContentJob($notification),
            fn () => NotificationCreated::dispatch($notification->fresh()),
        ])->catch(
            fn () => NotificationCreated::dispatch($notification->fresh())
        )->dispatch();
    }

    /**
     * Handle the Notification "updated" event.
     */
    public function updated(Notification $notification): void
    {
        //
    }

    /**
     * Handle the Notification "deleted" event.
     */
    public function deleted(Notification $notification): void
    {
        //
    }

    /**
     * Handle the Notification "restored" event.
     */
    public function restored(Notification $notification): void
    {
        //
    }

    /**
     * Handle the Notification "force deleted" event.
     */
    public function forceDeleted(Notification $notification): void
    {
        //
    }
}
