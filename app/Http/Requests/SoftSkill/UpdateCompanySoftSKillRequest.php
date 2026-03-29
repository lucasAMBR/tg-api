<?php

namespace App\Http\Requests\SoftSkill;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySoftSKillRequest extends FormRequest
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
        // dd('erro aq');
        return [
            'soft_skills' => ['sometimes', 'array'],
            // 'soft_skills.*.id' => ['required', 'string', 'exists:company_soft_skills,id'],
            'soft_skills.*.soft_skill_id' => ['sometimes', 'string', 'exists:soft_skills,id']
        ];
    }

    // public function prepareForValidation() {
    //     return $this->merge([
    //         'id' => $this->route('id')
    //     ]);
    // }
}
