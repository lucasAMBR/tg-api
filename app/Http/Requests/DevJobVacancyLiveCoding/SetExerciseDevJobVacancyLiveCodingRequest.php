<?php

namespace App\Http\Requests\DevJobVacancyLiveCoding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetExerciseDevJobVacancyLiveCodingRequest extends FormRequest
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
                'exists:dev_job_vacancy_live_codings,id'
            ],
            /**
             * Enunciado do exercício que o dev vai resolver na call
             */
            'statement' => [
                'required',
                'string',
                'max:5000'
            ],
            /**
             * Linguagem/stack esperada na resolução. Opcional: sem ela o dev resolve
             * na linguagem que preferir
             */
            'programming_language' => [
                'nullable',
                'string',
                'max:50'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
