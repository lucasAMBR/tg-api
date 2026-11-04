<?php

namespace App\Http\Requests\TechnicalChallenge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicalChallengeRequest extends FormRequest
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
            'rules' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'due_date' => [
                'required',
                'date',
                'after:today'
            ],
            'accepts_zip_submission' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);

        /**
         * Em multipart/form-data o booleano chega como texto ("true"/"false"), que a
         * regra `boolean` não aceita
         */
        $acceptsZipSubmission = $this->input('accepts_zip_submission');

        if (is_string($acceptsZipSubmission)) {
            $this->merge([
                'accepts_zip_submission' => filter_var($acceptsZipSubmission, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $acceptsZipSubmission
            ]);
        }
    }
}
