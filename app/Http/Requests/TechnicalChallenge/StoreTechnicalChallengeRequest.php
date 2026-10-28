<?php

namespace App\Http\Requests\TechnicalChallenge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicalChallengeRequest extends FormRequest
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
                'required',
                'string',
                'max:5000'
            ],
            /**
             * Regras da entrega: o que pode ser usado, o que será avaliado, limites
             */
            'rules' => [
                'nullable',
                'string',
                'max:5000'
            ],
            /**
             * Prazo de entrega do desafio, herdado por cada candidatura que entra na etapa
             */
            'due_date' => [
                'required',
                'date',
                'after:today'
            ],
            /**
             * Libera o envio do projeto compactado além do repositório
             */
            'accepts_zip_submission' => [
                'nullable',
                'boolean'
            ],
            /**
             * Documento guia com o enunciado completo do desafio
             */
            'guide_document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,md,txt,zip',
                'max:10000'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'job_vacancy_id' => $this->route('job_vacancy_id')
        ]);
    }
}
