<?php

namespace App\Http\Requests\SoftSkill;

use Illuminate\Foundation\Http\FormRequest;

class ListDevSoftSkillRequest extends FormRequest
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
            'dev_profile_id' => ['required', 'uuid', 'exists:dev_profiles,id']
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'dev_profile_id' => $this->route('dev_profile_id')
        ]);
    }
}
