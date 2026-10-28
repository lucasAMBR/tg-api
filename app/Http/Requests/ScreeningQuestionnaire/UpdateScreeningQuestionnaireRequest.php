<?php

namespace App\Http\Requests\ScreeningQuestionnaire;

use App\Enums\ScreeningQuestionTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateScreeningQuestionnaireRequest extends FormRequest
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
                'exists:screening_questionnaires,id'
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
            /**
             * A lista enviada substitui a atual: perguntas com `id` são atualizadas,
             * sem `id` são criadas e as que ficarem de fora são removidas
             */
            'questions' => [
                'required',
                'array',
                'min:1'
            ],
            'questions.*.id' => [
                'nullable',
                'uuid',
                'exists:screening_questions,id',
                'distinct'
            ],
            'questions.*.question' => [
                'required',
                'string',
                'max:1000'
            ],
            'questions.*.type' => [
                'required',
                new Enum(ScreeningQuestionTypeEnum::class)
            ],
            'questions.*.is_required' => [
                'nullable',
                'boolean'
            ],
            'questions.*.order' => [
                'required',
                'integer',
                'min:1',
                'distinct'
            ],
            'questions.*.options' => [
                'nullable',
                'array'
            ],
            'questions.*.options.*.id' => [
                'nullable',
                'uuid',
                'exists:screening_question_options,id'
            ],
            'questions.*.options.*.option' => [
                'required',
                'string',
                'max:500'
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
