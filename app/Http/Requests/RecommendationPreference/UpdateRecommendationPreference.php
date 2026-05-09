<?php

namespace App\Http\Requests\RecommendationPreference;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecommendationPreference extends FormRequest
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
            "allow_clt" => ['sometimes', 'boolean'],
            'allow_contractor' => ['sometimes', 'boolean'],
            'allow_internship' => ['sometimes', 'boolean'],
            'allow_on_site' => ['sometimes', 'boolean'],
            'allow_hybrid' => ['sometimes', 'boolean'],
            'allow_remote' => ['sometimes', 'boolean'],
            'on_site_job_radius' => ['sometimes', 'integer', 'min:10'],
            'hybrid_jobs_radius' => ['sometimes', 'integer', 'min:10'],
            'allow_stack_flexibility' => ['sometimes', 'boolean'],
            'min_remuneration' => ['sometimes', 'nullable', 'decimal:0,10'],
            'languages_blacklist' => ['sometimes', 'array'],
            'languages_blacklist.*' => ['string', 'exists:languages,id']
        ];
    }
}
