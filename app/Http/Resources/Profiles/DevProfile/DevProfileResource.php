<?php

namespace App\Http\Resources\Profiles\DevProfile;

use App\Enums\SeniorityLevelEnum;
use App\Http\Resources\AcademicBackground\AcademicBackgroundResource;
use App\Http\Resources\AdditionalCourse\AdditionalCourseResource;
use App\Http\Resources\Addresses\AddressResource;
use App\Http\Resources\DevSoftSkill\DevSoftSkillResource;
use App\Http\Resources\EmploymentHistory\EmploymentHistoryResource;
use App\Http\Resources\ProjectHistory\ProjectHistoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'bio' => $this->bio,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'seniority_level' => $this->seniority_level,
            'seniority_level_label' => SeniorityLevelEnum::from($this->seniority_level)->label(),
            'birthdate' => $this->birthdate,
            'score' => $this->score,
            'open_to_work' => $this->open_to_work,
            'open_to_relocation' => $this->open_to_relocation,
            'employment_histories' => $this->whenLoaded('employment_histories', function () {
                return EmploymentHistoryResource::collection($this->employment_histories);
            }),
            'project_histories' => $this->whenLoaded('project_histories', function () {
                return ProjectHistoryResource::collection($this->project_histories);
            }),
            'academic_backgrounds' =>  $this->whenLoaded('academic_backgrounds', function () {
                return AcademicBackgroundResource::collection($this->academic_backgrounds);
            }),
            'additional_courses' => $this->whenLoaded('additional_courses', function () {
                return AdditionalCourseResource::collection($this->additional_courses);
            }),
            'dev_soft_skills' => $this->whenLoaded('dev_soft_skills', function () {
                return DevSoftSkillResource::collection($this->dev_soft_skills);
            }),
            'address' => $this->whenLoaded('address', function () {
                return new AddressResource($this->address);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
