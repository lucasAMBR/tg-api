<?php

namespace App\Http\Requests\DevJobVacancyInterview;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetInitialScheduleDevJobVacancyInterviewRequest extends FormRequest
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
                'exists:dev_job_vacancy_interviews,id'
            ],
            'scheduled_at' => [
                'required',
                'date',
                'after:now'
            ],
            'duration_in_minutes' => [
                'required',
                'integer',
                'min:5',
                'max:1440'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
