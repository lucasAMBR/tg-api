<?php

namespace App\Http\Requests\TechnicalChallenge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveGuideDocumentRequest extends FormRequest
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
                'exists:technical_challenges,id'
            ],
            /**
             * Documento guia com o enunciado completo do desafio. O envio substitui o
             * documento anterior, quando já existe um
             */
            'guide_document' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,md,txt,zip',
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
