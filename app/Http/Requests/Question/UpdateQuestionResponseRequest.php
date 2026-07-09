<?php

namespace App\Http\Requests\Question;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionResponseRequest extends FormRequest
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
            'response' => ['required', 'string', 'max:255'],
            'response_pt' => ['required_if:manual_update_translation,true', 'string', 'max:255'],
            'response_en' => ['required_if:manual_update_translation,true', 'string', 'max:255'],
            'is_correct' => ['required', 'boolean'],
            'code_snippet' => ['nullable', 'array', 'max:255'],
            'manual_update_translation' => ['required', 'boolean'],
        ];
    }
}
