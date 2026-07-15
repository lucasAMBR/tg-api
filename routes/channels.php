<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications.{type}.{id}', function (User $user, string $type, string $id) {
    return match($type) {
        'developer' => $user->dev_profile?->id === $id,
        'company' => $user->company_profile?->id === $id,
        'client' => $user->client_profile?->id == $id,
        'admin' => $user->admin_profile?->id === $id,
        default => false
    };
});