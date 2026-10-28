<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'type' => $this->type,
            'title' => $this->title,
            'title_pt' => $this->title_pt,
            'title_en' => $this->title_en,
            'message' => $this->message,
            'message_pt' => $this->message_pt,
            'message_en' => $this->message_en,
            'translation_status' => $this->translation_status,
            'read_at' => $this->read_at,
            'link' => $this->link,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
