<?php

namespace App\Http\Resources\CompanySoftSkill;

use App\Http\Resources\SoftSkill\SoftSkillResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySoftSkillResource extends JsonResource
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
            'soft_skill_id' => $this->soft_skill_id,
            'soft_skill' => $this->whenLoaded('soft_skills', function() {
                return new SoftSkillResource($this->soft_skills);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String()
        ];
    }
}
