<?php

namespace App\Http\Requests\Question;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddResponseToQuestionRequest extends FormRequest
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
            'question_id' => ['required', 'uuid', 'exists:questions,id'],
            'response' => ['required', 'string', 'max:255'],
            'is_correct' => ['required', 'boolean'],
            'code_snippet' => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'question_id' => $this->route('question_id')
        ]);
    }
}
