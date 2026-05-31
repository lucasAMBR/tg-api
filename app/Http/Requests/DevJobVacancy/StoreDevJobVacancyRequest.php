<?php

namespace App\Http\Requests\DevJobVacancy;

use App\Enums\JobVacancyStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDevJobVacancyRequest extends FormRequest
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
            'status' => [
                'required',
                new Enum(JobVacancyStatusEnum::class)
            ],
            'feedback' => [
                'sometimes',
                'string'
            ],
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'job_vacancy_id' => $this->route('jobVacancyId')
        ]);
    }
}
