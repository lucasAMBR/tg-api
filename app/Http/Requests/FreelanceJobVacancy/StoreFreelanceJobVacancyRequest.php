<?php

namespace App\Http\Requests\FreelanceJobVacancy;

use App\Enums\FreelanceJobTypeEnum;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SalaryTypeEnum;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreFreelanceJobVacancyRequest extends FormRequest
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
                'required',
                'string',
            ],
            'description' => [
                'required',
                'string',
            ],
            'job_type' => [
                'required',
                new Enum(FreelanceJobTypeEnum::class),
            ],
            'salary_type' => [
                'required',
                new Enum(SalaryTypeEnum::class),
            ],
            'estimated_salary' => [
                'required',
                'numeric',
                'min:0',
            ],
            'specialties' => [
                'required',
                'array',
            ],
            'specialties.*' => [
                'required',
                'string',
            ],
            'seniority_level' => [
                'required',
                new Enum(SeniorityLevelEnum::class),
            ],
            'languages' => [
                Rule::requiredIf(fn() => $this->requiresStack()),
                'array',
            ],
            'languages.*.language_id' => [
                'required',
                'uuid',
                'distinct',
                'exists:languages,id',
            ],
            'languages.*.language_level' => [
                'required',
                new Enum(HardSkillLevelsEnum::class),
            ],
        ];
    }

    protected function requiresStack(): bool
    {
        $jobType = FreelanceJobTypeEnum::tryFrom((string) $this->input('job_type'));

        return $jobType?->requiresStack() ?? false;
    }

    public function messages(): array
    {
        return [
            'languages.required' => 'Vagas do tipo selecionado exigem ao menos uma linguagem ou stack.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Título',
            'description' => 'Descrição',
            'job_type' => 'Tipo de serviço',
            'salary_type' => 'Tipo de salário',
            'estimated_salary' => 'Salário estimado',
            'specialties' => 'Especialidades',
            'seniority_level' => 'Nível de senioridade',
            'languages' => 'Linguagens',
            'languages.*.language_id' => 'ID da linguagem',
            'languages.*.language_level' => 'Nível da linguagem'
        ];
    }
}
