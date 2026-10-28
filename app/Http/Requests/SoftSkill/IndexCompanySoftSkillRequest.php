<?php

namespace App\Http\Requests\SoftSkill;

use Illuminate\Foundation\Http\FormRequest;

class IndexCompanySoftSkillRequest extends FormRequest
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
            'company_profile_id' => ['required', 'uuid', 'exists:company_profiles,id']
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'company_profile_id' => $this->route('company_profile_id')
        ]);
    }
}
