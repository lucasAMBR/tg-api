<?php

namespace App\Http\Resources\FreelanceJobVacancy;

use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelanceJobVacancyResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'job_type' => $this->job_type?->value,
            'job_type_label' => $this->job_type?->label(),
            'requires_stack' => $this->job_type?->requiresStack(),
            'salary_type' => $this->salary_type?->value,
            'salary_type_label' => $this->salary_type?->label(),
            'estimated_salary' => $this->estimated_salary,
            'seniority_level' => $this->seniority_level?->value,
            'seniority_level_label' => $this->seniority_level?->label(),
            'specialties' => $this->specialties,
            'languages' => FreelanceJobVacancyLanguageResource::collection($this->whenLoaded('languages')),
            'client_profile_id' => $this->client_profile_id,
            'profile' => new ClientProfileResource($this->whenLoaded('clientProfile')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
