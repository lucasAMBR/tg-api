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
            'i18n_name_key' => $this->i18n_name_key,
            'description' => $this->description,
            'i18n_description_key' => $this->i18n_description_key,
            'responses' => $this->whenLoaded('responses', function () {
                return SoftSkillLevelResponseResource::collection($this->responses);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
