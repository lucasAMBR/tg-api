<?php

namespace App\Http\Requests\JobVacancyInvitation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobVacancyInvitationRequest extends FormRequest
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
            'id' => [
                'required',
                'uuid',
                'exists:job_vacancies,id,deleted_at,NULL'
            ],
            'dev_profile_id' => [
                'required',
                'uuid',
                'exists:dev_profiles,id,deleted_at,NULL'
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }

    public function attributes(): array
    {
        return [
            'dev_profile_id' => 'Perfil do desenvolvedor',
        ];
    }
}
