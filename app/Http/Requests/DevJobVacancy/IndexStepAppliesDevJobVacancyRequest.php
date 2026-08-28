<?php

namespace App\Http\Requests\DevJobVacancy;

use App\Enums\SelectionProcessStageEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexStepAppliesDevJobVacancyRequest extends FormRequest
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
            'job_vacancy_id' => ['required', 'uuid', 'exists:job_vacancies,id'],
            'process_step' => ['nullable', new Enum(SelectionProcessStageEnum::class)],
            'search' => ['nullable', 'string']
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'job_vacancy_id' => $this->route('job_vacancy_id')
        ]);
    }
}
