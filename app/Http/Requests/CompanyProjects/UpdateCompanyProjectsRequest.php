<?php

namespace App\Http\Requests\CompanyProjects;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProjectsRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['sometimes', 'exists:languages,id'],
            'prod_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'github_url' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
