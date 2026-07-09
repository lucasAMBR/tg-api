<?php

namespace App\Http\Requests\Question;

use App\Enums\SeniorityLevelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:255'],
            'question_pt' => ['required_if:manual_update_translation,true', 'string', 'max:255'],
            'question_en' => ['required_if:manual_update_translation,true', 'string', 'max:255'],
            'difficulty_level' => ['required', 'integer', 'min:1', 'max:10'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'category' => ['required', 'string'],
            'ideal_time_to_solve' => ['required', 'integer', 'min:1'],
            'code_snippet' => ['nullable', 'array', 'max:255'],
            'seniority_level' => ['required', 'string', Rule::enum(SeniorityLevelEnum::class)],
            'is_multiple_choice' => ['required', 'boolean'],
            'manual_update_translation' => ['required', 'boolean'],
        ];
    }
}
