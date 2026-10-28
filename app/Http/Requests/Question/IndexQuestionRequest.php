<?php

namespace App\Http\Requests\Question;

use App\Enums\SeniorityLevelEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexQuestionRequest extends FormRequest
{
    use IndexRequestTrait;
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
        return array_merge($this->paginationRules(), [
            'difficulty_level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'category' => ['nullable', 'string'],
            'seniority_level' => ['nullable', 'string', Rule::enum(SeniorityLevelEnum::class)],
            'is_multiple_choice' => ['nullable', 'boolean'],
            'translation_status' => ['nullable', 'boolean'],
        ]);
    }
}
