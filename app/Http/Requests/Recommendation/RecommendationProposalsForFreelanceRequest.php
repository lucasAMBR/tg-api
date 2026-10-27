<?php

namespace App\Http\Requests\Recommendation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecommendationProposalsForFreelanceRequest extends FormRequest
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
            'freelance_job_vacancy_id' => ['required', 'uuid', 'exists:freelance_job_vacancies,id,deleted_at,NULL'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50']
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'freelance_job_vacancy_id' => $this->route('freelance_job_vacancy_id')
        ]);
    }
}
