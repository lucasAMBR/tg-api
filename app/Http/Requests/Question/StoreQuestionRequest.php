<?php

namespace App\Http\Requests\Question;

use App\Enums\SeniorityLevelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'difficulty_level' => ['required', 'integer', 'min:1', 'max:10'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'category' => ['required', 'string'],
            'ideal_time_to_solve' => ['required', 'integer', 'min:1'],
            'code_snippet' => ['nullable', 'array', 'max:255'],
            'seniority_level' => ['required', 'string', Rule::enum(SeniorityLevelEnum::class)],
            'is_multiple_choice' => ['required', 'boolean'],
            'responses' => ['required', 'array', 'min:4', function ($attribute, $value, $fail) {
                $correctCount = collect($value)->filter(fn ($answer) => $answer['is_correct'])->count();

                if($correctCount === 0) {
                    $fail('The question must have at least one correct answer.');
                    return;
                }

                $isMultipleChoice = $this->boolean('is_multiple_choice');

                if($correctCount > 1 && !$isMultipleChoice) {
                    $fail('The question must be multiple choice if it has more than one correct answer.');
                }
            }],
            'responses.*.response' => ['required', 'string', 'max:255'],
            'responses.*.is_correct' => ['required', 'boolean'],
            'responses.*.code_snippet' => ['nullable', 'string', 'max:255'],
        ];
    }
}
