<?php

namespace App\Http\Resources\DevSoftSkill;

use App\Http\Resources\SoftSkill\SoftSkillResource;
use App\Http\Resources\SoftSkillLevelResponse\SoftSkillLevelResponseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevSoftSkillResource extends JsonResource
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
            'soft_skill' => $this->whenLoaded('soft_skill', function () {
                return new SoftSkillResource($this->soft_skill);
            }),
            'soft_skill_level_response_id' => $this->soft_skill_level_response_id,
            'soft_skill_level_response' => $this->whenLoaded('soft_skill_level_response', function () {
                return new SoftSkillLevelResponseResource($this->soft_skill_level_response);
            }),
        ];
    }
}
