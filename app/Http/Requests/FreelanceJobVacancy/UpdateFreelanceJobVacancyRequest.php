<?php

namespace App\Http\Requests\FreelanceJobVacancy;

use App\Enums\FreelanceJobTypeEnum;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SalaryTypeEnum;
use App\Enums\SeniorityLevelEnum;
use App\Models\FreelanceJobVacancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateFreelanceJobVacancyRequest extends FormRequest
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
            'id' => [
                'required',
                'uuid',
                'exists:freelance_job_vacancies,id',
            ],
            'title' => [
                'sometimes',
                'string',
            ],
            'description' => [
                'sometimes',
                'string',
            ],
            'job_type' => [
                'sometimes',
                new Enum(FreelanceJobTypeEnum::class),
            ],
            'salary_type' => [
                'sometimes',
                new Enum(SalaryTypeEnum::class),
            ],
            'estimated_salary' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'specialties' => [
                'sometimes',
                'array',
            ],
            'specialties.*' => [
                'required',
                'string',
            ],
            'seniority_level' => [
                'sometimes',
                new Enum(SeniorityLevelEnum::class),
            ],
            // Quando enviado, o array substitui por completo as linguagens da vaga (sync).
            'languages' => [
                'sometimes',
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

    /**
     * Um PATCH pode trocar o tipo de serviço para um que exige stack sem enviar linguagens,
     * ou esvaziar as linguagens mantendo um tipo que exige stack. Nos dois casos a vaga
     * terminaria inválida, então a checagem considera o estado final (payload + banco).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $vacancy = FreelanceJobVacancy::find($this->input('id'));

            if (!$vacancy) {
                return;
            }

            $jobType = $this->has('job_type')
                ? FreelanceJobTypeEnum::tryFrom((string) $this->input('job_type'))
                : $vacancy->job_type;

            if (!$jobType?->requiresStack()) {
                return;
            }

            $hasLanguages = $this->has('languages')
                ? !empty($this->input('languages'))
                : $vacancy->languages()->exists();

            if (!$hasLanguages) {
                $validator->errors()->add(
                    'languages',
                    'Vagas do tipo selecionado exigem ao menos uma linguagem ou stack.'
                );
            }
        });
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
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
