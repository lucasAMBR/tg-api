<?php

namespace App\Http\Requests\JobVacancy;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreJobVacancyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string'
            ],
            'description' => [
                'required',
                'string',
            ],
            'employment_type' => [
                'required',
                new Enum(EmploymentType::class)
            ],
            'benefits' => [
                'required',
                'array'
            ],
            'benefits.*' => [
                'required',
                'string'
            ],
            'estimated_salary' => [
                'required',
                'numeric'
            ],
            'contract_type' => [
                'required',
                new Enum(ContractType::class)
            ],
            'seniority_level' => [
                'required',
                new Enum(SeniorityLevelEnum::class)
            ],
            'languages' => [
                'required',
                'array'
            ],
            'languages.*.languages_id' => [
                'required',
                'uuid',
                'exists:languages,id'
            ],
            'languages.*.language_level' => [
                'required',
                new Enum(HardSkillLevelsEnum::class)
            ],
            'soft_skills' => [
                'required',
                'array'
            ],
            'soft_skills.*.soft_skills_id' => [
                'required',
                'string',
                'exists:soft_skills,id'
            ]
        ];
    }
}


