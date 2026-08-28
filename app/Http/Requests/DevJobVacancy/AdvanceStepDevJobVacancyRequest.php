<?php

namespace App\Http\Requests\DevJobVacancy;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdvanceStepDevJobVacancyRequest extends FormRequest
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
            'job_vacancy_id' => [
                'required',
                'uuid',
                'exists:job_vacancies,id'
            ],
            // Lista pode vir vazia, o que significa recusar todas as candidaturas da etapa
            'apply_ids' => [
                'present',
                'array'
            ],
            'apply_ids.*' => [
                'required',
                'uuid',
                'distinct',
                'exists:dev_job_vacancy,id'
            ],
            // Prazo de entrega do portfólio, usado quando a próxima etapa é a análise de portfólio
            'due_date' => [
                'nullable',
                'date',
                'after:today'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'job_vacancy_id' => $this->route('job_vacancy_id')
        ]);
    }
}
