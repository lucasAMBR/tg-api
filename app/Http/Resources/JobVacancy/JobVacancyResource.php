<?php

namespace App\Http\Resources\JobVacancy;

use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\SoftSkill\SoftSkillResource;
use App\Models\JobVacancyLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobVacancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'employment_type' => $this->employment_type,
            'benefits' => $this->benefits,
            'estimated_salary' => $this->estimated_salary,
            'contract_type' => $this->contract_type,
            'seniority_level' => $this->seniority_level,
            'specialties' => $this->specialties,
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'soft_skills' => SoftSkillResource::collection($this->whenLoaded('softSkill')),
            'language_desirable' => LanguageResource::collection($this->whenLoaded('desirableLanguage')),
            'company_profile_id' => $this->company_profile_id,
            'profile' => new CompanyProfileResource($this->whenLoaded('companyProfile'))
        ];
    }
}
