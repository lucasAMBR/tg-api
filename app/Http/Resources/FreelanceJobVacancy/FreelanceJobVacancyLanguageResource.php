<?php

namespace App\Http\Resources\FreelanceJobVacancy;

use App\Enums\HardSkillLevelsEnum;
use App\Models\FreelanceJobVacancyLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Linguagem vinculada a uma vaga freelance, já com o nível exigido vindo da pivot.
 *
 * Existe separado do `LanguageResource` porque aquele expõe o `language_level`
 * apenas quando a pivot carregada é a de vagas de empresa (`JobVacancyLanguage`).
 */
class FreelanceJobVacancyLanguageResource extends JsonResource
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
            'language_level' => $this->whenPivotLoaded(FreelanceJobVacancyLanguage::class, fn() => $this->pivot->language_level),
            'language_level_label' => $this->whenPivotLoaded(
                FreelanceJobVacancyLanguage::class,
                fn() => HardSkillLevelsEnum::labelFromValue($this->pivot->language_level?->value)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
