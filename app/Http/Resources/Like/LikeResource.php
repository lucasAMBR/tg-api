<?php

namespace App\Http\Resources\Like;

use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
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
            'likeable_id' => $this->likeable_id,
            'likeable_type' => $this->likeable_type,
            'user' => $this->whenLoaded('user', function () {
                return new UserSimpleResource($this->user);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
