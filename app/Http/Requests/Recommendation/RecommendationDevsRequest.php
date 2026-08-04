<?php

namespace App\Http\Requests\Recommendation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecommendationDevsRequest extends FormRequest
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
            'job_vacancy_id' => ['required', 'uuid', 'exists:job_vacancies,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_similarity' => ['nullable', 'numeric', 'min:0', 'max:1']
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'job_vacancy_id' => $this->route('job_vacancy_id')
        ]);
    }
}
