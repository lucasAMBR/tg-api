<?php

namespace App\Http\Requests\LanguageAssessment;

use App\Enums\LanguageAssessmentQuestionTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLanguageAssessmentRequest extends FormRequest
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
             * Idioma avaliado, escrito pela empresa (ex.: "Inglês", "Español")
             */
            'language' => [
                'required',
                'string',
                'max:50'
            ],
            /**
             * Nota mínima de aprovação. Sem ela a prova pontua, mas não dá veredito.
             * O limite contra a soma dos pesos é validado no service
             */
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
            'questions.*.question' => [
                'required',
                'string',
                'max:1000'
            ],
            'questions.*.type' => [
                'required',
                new Enum(LanguageAssessmentQuestionTypeEnum::class)
            ],
            /**
             * Quanto a pergunta vale na nota final
             */
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
            /**
             * Respostas aceitas nas perguntas escritas. Obrigatórias no tipo `text` e
             * proibidas nas de escolha — a regra é validada no service
             */
            'questions.*.expected_answers' => [
                'nullable',
                'array'
            ],
            'questions.*.expected_answers.*' => [
                'required',
                'string',
                'max:255'
            ],
            /**
             * Obrigatórias nas perguntas de escolha e proibidas nas escritas. O número
             * mínimo de alternativas e o gabarito são validados no service
             */
            'questions.*.options' => [
                'nullable',
                'array'
            ],
            'questions.*.options.*.option' => [
                'required',
                'string',
                'max:500'
            ],
            /**
             * Marca a alternativa como a resposta certa
             */
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
            'job_vacancy_id' => $this->route('job_vacancy_id')
        ]);
    }
}
