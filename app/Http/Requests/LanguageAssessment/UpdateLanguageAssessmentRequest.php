<?php

namespace App\Http\Requests\LanguageAssessment;

use App\Enums\LanguageAssessmentQuestionTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateLanguageAssessmentRequest extends FormRequest
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
                'exists:language_assessments,id'
            ],
            'title' => [
                'required',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'language' => [
                'required',
                'string',
                'max:50'
            ],
            'passing_score' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'due_date' => [
                'required',
                'date',
                'after:today'
            ],
            'questions' => [
                'required',
                'array',
                'min:1'
            ],
            /**
             * Perguntas com `id` são atualizadas, sem `id` são criadas e as que ficarem
             * de fora da lista são removidas. O mesmo vale para as alternativas
             */
            'questions.*.id' => [
                'nullable',
                'uuid'
            ],
            'questions.*.question' => [
                'required',
                'string',
                'max:1000'
            ],
            'questions.*.type' => [
                'required',
                new Enum(LanguageAssessmentQuestionTypeEnum::class)
            ],
            'questions.*.weight' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'questions.*.order' => [
                'required',
                'integer',
                'min:1',
                'distinct'
            ],
            'questions.*.expected_answers' => [
                'nullable',
                'array'
            ],
            'questions.*.expected_answers.*' => [
                'required',
                'string',
                'max:255'
            ],
            'questions.*.options' => [
                'nullable',
                'array'
            ],
            'questions.*.options.*.id' => [
                'nullable',
                'uuid'
            ],
            'questions.*.options.*.option' => [
                'required',
                'string',
                'max:500'
            ],
            'questions.*.options.*.is_correct' => [
                'nullable',
                'boolean'
            ],
            'questions.*.options.*.order' => [
                'required',
                'integer',
                'min:1'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
