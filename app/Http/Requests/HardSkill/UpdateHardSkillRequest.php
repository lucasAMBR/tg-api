<?php

namespace App\Http\Requests\HardSkill;

use App\Enums\HardSkillLevelsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHardSkillRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language_id' => ['sometimes', 'string', 'exists:languages,id'],
            'skill_level' => ['sometimes', Rule::enum(HardSkillLevelsEnum::class)]
        ];
    }
}
