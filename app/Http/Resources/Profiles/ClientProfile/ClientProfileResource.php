<?php

namespace App\Http\Resources\Profiles\ClientProfile;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'name' => $this->name,
            'bio' => $this->bio,
            'bio_pt' => $this->bio_pt,
            'bio_en' => $this->bio_en,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'birthdate' => $this->birthdate,
            'score' => $this->score,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
