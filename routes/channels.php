<?php

use App\Models\DevJobVacancyInterview;
use App\Models\DevJobVacancyLiveCoding;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal usado para a sinalização WebRTC (offer/answer/ICE candidates) da call de
// entrevista. Só os 2 participantes daquela entrevista específica podem entrar
Broadcast::channel('interview.{id}', function (User $user, string $id) {

    $interview = DevJobVacancyInterview::with(['devJobVacancy', 'jobVacancy'])->find($id);

    if (!$interview) {
        return false;
    }

    return $user->dev_profile?->id === $interview->devJobVacancy?->dev_profile_id
        || $user->company_profile?->id === $interview->jobVacancy?->company_profile_id;
});

// Canal usado para a sinalização WebRTC (offer/answer/ICE candidates) da call de
// live coding. Só os 2 participantes daquele live coding específico podem entrar
Broadcast::channel('live-coding.{id}', function (User $user, string $id) {

    $liveCoding = DevJobVacancyLiveCoding::with(['devJobVacancy', 'jobVacancy'])->find($id);

    if (!$liveCoding) {
        return false;
    }

    return $user->dev_profile?->id === $liveCoding->devJobVacancy?->dev_profile_id
        || $user->company_profile?->id === $liveCoding->jobVacancy?->company_profile_id;
});

// Canal pessoal do chat: cada usuário só escuta o próprio. Chegam por ele as mensagens
// novas de qualquer conversa (`message.sent`) e as confirmações de leitura (`messages.read`)
Broadcast::channel('chat.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
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