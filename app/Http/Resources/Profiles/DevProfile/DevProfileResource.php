<?php

namespace App\Http\Resources\Profiles\DevProfile;

use App\Enums\SeniorityLevelEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevProfileResource extends JsonResource
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
            'bio' => $this->bio,
            'seniority_level' => SeniorityLevelEnum::from($this->seniority_level)->label(),
            'birthdate' => $this->birthdate,
            'score' => $this->score,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
