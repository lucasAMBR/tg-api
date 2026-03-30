<?php

namespace App\Http\Resources\HardSkill;

use App\Enums\HardSkillLevelsEnum;
use App\Http\Resources\Language\LanguageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardSkillResource extends JsonResource
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
            'dev_profile_id' => $this->dev_profile_id,
            'skill_level' => $this->skill_level,
            'skill_level_label' => HardSkillLevelsEnum::labelFromValue($this->skill_level),
            'language' => new LanguageResource($this->language),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
