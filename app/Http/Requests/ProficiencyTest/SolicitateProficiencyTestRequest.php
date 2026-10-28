<?php

namespace App\Http\Requests\ProficiencyTest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SolicitateProficiencyTestRequest extends FormRequest
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
            'dev_profile_id' => ['required', 'uuid', 'exists:dev_profiles,id'],
            'backend_category' => ['required_without:frontend_category', 'nullable', 'string', 'max:255'],
            'frontend_category' => ['required_without:backend_category', 'nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'dev_profile_id' => $this->route('dev_profile_id')
        ]);
    }
}
