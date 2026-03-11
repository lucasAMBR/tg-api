<?php

namespace App\Http\Resources\SoftSkillLevelResponse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftSkillLevelResponseResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'evaluation_weight' => $this->evaluation_weight
        ];
    }
}
