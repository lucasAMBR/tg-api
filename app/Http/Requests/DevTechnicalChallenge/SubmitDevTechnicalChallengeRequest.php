<?php

namespace App\Http\Requests\DevTechnicalChallenge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitDevTechnicalChallengeRequest extends FormRequest
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
                'exists:dev_technical_challenges,id'
            ],
            /**
             * Repositório com a solução do desafio
             */
            'repository_url' => [
                'required',
                'url',
                'max:255'
            ],
            /**
             * Projeto compactado, aceito só quando o desafio permite o envio do zip.
             * A regra é validada no service, junto com o resto das regras de negócio
             */
            'submission_file' => [
                'nullable',
                'file',
                'mimes:zip',
                'max:10000'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
