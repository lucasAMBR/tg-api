<?php

namespace App\Http\Resources\Post;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'content' => $this->content,
            'user_id' => $this->user_id,
            'author' => $this->whenLoaded('user', function () {
                return new UserSimpleResource($this->user);
            }),
            'images' => $this->getMedia('images')->map(function ($media) {
                return [
                    'id' => (int) $media->id,
                    'original_url' => (string) $media->getUrl(),
                    'thumb_url' => (string) $media->getUrl('thumb'),
                ];
            })->values(),
            'likes_count' => (int) $this->likes_count,
            'comments_count' => (int) $this->comments_count,
            'favorites_count' => (int) $this->favorites_count,
            'is_liked' => (bool) $this->is_liked,
            'is_favorited' => (bool) $this->is_favorited,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
