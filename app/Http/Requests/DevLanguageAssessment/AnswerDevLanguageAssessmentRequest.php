<?php

namespace App\Http\Requests\DevLanguageAssessment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnswerDevLanguageAssessmentRequest extends FormRequest
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
                'exists:dev_language_assessments,id'
            ],
            'answers' => [
                'required',
                'array',
                'min:1'
            ],
            'answers.*.question_id' => [
                'required',
                'uuid',
                'distinct'
            ],
            /**
             * Texto da resposta nas perguntas escritas
             */
            'answers.*.response' => [
                'nullable',
                'string',
                'max:1000'
            ],
            /**
             * Alternativas marcadas nas perguntas de escolha: uma na escolha única,
             * uma ou mais na múltipla escolha
             */
            'answers.*.option_ids' => [
                'nullable',
                'array'
            ],
            'answers.*.option_ids.*' => [
                'required',
                'uuid'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
