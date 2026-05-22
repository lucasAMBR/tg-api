<?php

namespace App\Http\Requests\RecommendationPreference;

use App\Models\Language;
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
            'min_remuneration' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10000'],
            'languages_blacklist' => ['sometimes', 'array'],
            'languages_blacklist.*' => ['string', 'exists:languages,id']
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                $this->has('allow_clt')
                && $this->has('allow_contractor')
                && $this->has('allow_internship')
                && ! $this->boolean('allow_clt')
                && ! $this->boolean('allow_contractor')
                && ! $this->boolean('allow_internship')
            ) {
                $validator->errors()->add(
                    'employment_types',
                    'At least one employment type must be enabled.'
                );
            }

            if (
                $this->has('allow_on_site')
                && $this->has('allow_hybrid')
                && $this->has('allow_remote')
                && ! $this->boolean('allow_on_site')
                && ! $this->boolean('allow_hybrid')
                && ! $this->boolean('allow_remote')
            ) {
                $validator->errors()->add(
                    'work_modes',
                    'At least one work mode must be enabled.'
                );
            }

            if ($this->has('languages_blacklist')) {
                $blacklist = $this->input('languages_blacklist', []);
                $totalLanguages = Language::count();

                if ($totalLanguages > 0 && count($blacklist) >= $totalLanguages) {
                    $validator->errors()->add(
                        'languages_blacklist',
                        'You cannot blacklist all available languages.'
                    );
                }
            }
        });
    }
}
