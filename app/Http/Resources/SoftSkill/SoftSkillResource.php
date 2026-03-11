<?php

namespace App\Http\Resources\SoftSkill;

use App\Http\Resources\SoftSkillLevelResponse\SoftSkillLevelResponseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftSkillResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'responses' => $this->whenLoaded('responses', function () {
                return SoftSkillLevelResponseResource::collection($this->responses);
            })
        ];
    }
}
