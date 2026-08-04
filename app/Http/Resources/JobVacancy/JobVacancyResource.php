<?php

namespace App\Http\Resources\JobVacancy;

use App\Enums\TranslationStatusEnum;
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
            'title_pt' => $this->title_pt,
            'title_en' => $this->title_en,
            'description' => $this->description,
            'description_pt' => $this->description_pt,
            'description_en' => $this->description_en,
            'employment_type' => $this->employment_type,
            'benefits' => $this->benefits,
            'benefits_pt' => $this->benefits_pt,
            'benefits_en' => $this->benefits_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'estimated_salary' => $this->estimated_salary,
            'contract_type' => $this->contract_type,
            'seniority_level' => $this->seniority_level,
            'specialties' => $this->specialties,
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'soft_skills' => SoftSkillResource::collection($this->whenLoaded('softSkill')),
            'language_desirable' => LanguageResource::collection($this->whenLoaded('desirableLanguage')),
            'process_steps' => JobVacancyProcessStepResource::collection($this->whenLoaded('processSteps')),
            'company_profile_id' => $this->company_profile_id,
            'profile' => new CompanyProfileResource($this->whenLoaded('companyProfile')),
            'dev_profiles_count' => $this->whenCounted('devProfiles')
        ];
    }
}
