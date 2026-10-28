<?php

namespace App\Http\Requests\FreelanceJobVacancy;

use App\Enums\DevSpecialtyEnum;
use App\Enums\FreelanceJobTypeEnum;
use App\Enums\HardSkillLevelsEnum;
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
                'max:255',
            ],
            'description' => [
                'required',
                'string',
            ],
            'job_type' => [
                'required',
                new Enum(FreelanceJobTypeEnum::class),
            ],
            // Quanto o cliente pretende pagar pelo serviço inteiro; só ele vê
            'budget' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'specialties' => [
                'required',
                'array',
                'min:1',
            ],
            'specialties.*' => [
                'required',
                'distinct',
                new Enum(DevSpecialtyEnum::class),
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
            'budget' => 'Orçamento',
            'specialties' => 'Especialidades',
            'specialties.*' => 'Especialidade',
            'seniority_level' => 'Nível de senioridade',
            'languages' => 'Linguagens',
            'languages.*.language_id' => 'ID da linguagem',
            'languages.*.language_level' => 'Nível da linguagem'
        ];
    }
}
