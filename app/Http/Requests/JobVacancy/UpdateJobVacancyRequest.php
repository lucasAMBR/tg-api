<?php

namespace App\Http\Requests\JobVacancy;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateJobVacancyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string'
            ],
            'description' => [
                'sometimes',
                'string',
            ],
            'employment_type' => [
                'sometimes',
                new Enum(EmploymentType::class)
            ],
            'benefits' => [
                'sometimes',
                'array'
            ],
            'benefits.*' => [
                'sometimes',
                'string'
            ],
            'estimated_salary' => [
                'sometimes',
                'numeric'
            ],
            'contract_type' => [
                'sometimes',
                new Enum(ContractType::class)
            ],
            'seniority_level' => [
                'sometimes',
                new Enum(SeniorityLevelEnum::class)
            ],
            'languages' => [
                'sometimes',
                'array'
            ],
            'languages.*.current_languages_id' => [
                'sometimes',
                'uuid',
                'exists:languages,id'
            ],
            'languages.*.new_languages_id' => [
                'sometimes',
                'uuid',
                'exists:languages,id'
            ],
            'languages.*.language_level' => [
                'sometimes',
                new Enum(HardSkillLevelsEnum::class)
            ],
            'soft_skills' => [
                'sometimes',
                'array'
            ],
            'soft_skills.*.current_soft_skills_id' => [
                'sometimes',
                'string',
                'exists:soft_skills,id'
            ],
            'soft_skills.*.new_soft_skills_id' => [
                'sometimes',
                'string',
                'exists:soft_skills,id'
            ]
        ];
    }
}
