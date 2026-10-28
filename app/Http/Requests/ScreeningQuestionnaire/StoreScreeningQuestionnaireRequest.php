<?php

namespace App\Http\Requests\ScreeningQuestionnaire;

use App\Enums\ScreeningQuestionTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreScreeningQuestionnaireRequest extends FormRequest
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
             * Prazo de resposta, usado quando a vaga já está aguardando o questionário:
             * ao ser cadastrado, ele vai direto para os candidatos parados na espera
             */
            'due_date' => [
                'nullable',
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
            /**
             * Obrigatórias nas perguntas de escolha e proibidas nas dissertativas.
             * A regra é validada no service, junto com o resto das regras de negócio
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
