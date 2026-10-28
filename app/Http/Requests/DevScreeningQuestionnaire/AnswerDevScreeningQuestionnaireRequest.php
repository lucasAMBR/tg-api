<?php

namespace App\Http\Requests\DevScreeningQuestionnaire;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnswerDevScreeningQuestionnaireRequest extends FormRequest
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
                'exists:dev_screening_questionnaires,id'
            ],
            'answers' => [
                'required',
                'array',
                'min:1'
            ],
            'answers.*.question_id' => [
                'required',
                'uuid',
                'exists:screening_questions,id',
                'distinct'
            ],
            /**
             * Texto das dissertativas. Nas perguntas de escolha o conteúdo vem em
             * `option_ids` — a combinação certa de cada tipo é validada no service
             */
            'answers.*.response' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'answers.*.option_ids' => [
                'nullable',
                'array'
            ],
            'answers.*.option_ids.*' => [
                'required',
                'uuid',
                'exists:screening_question_options,id',
                'distinct'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
