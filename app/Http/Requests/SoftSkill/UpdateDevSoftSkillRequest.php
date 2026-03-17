<?php

namespace App\Http\Requests\SoftSkill;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDevSoftSkillRequest extends FormRequest
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
            'soft_skills' => ['required', 'array', 'min:10', 'max:10'],
            'soft_skills.*.soft_skill_id' => ['required', 'string', 'exists:soft_skills,id'],
            'soft_skills.*.soft_skill_level_response_id' => ['required', 'string', 'exists:soft_skill_level_responses,id']
        ];
    }
}
