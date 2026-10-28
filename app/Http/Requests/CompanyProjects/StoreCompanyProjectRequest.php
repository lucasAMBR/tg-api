<?php

namespace App\Http\Requests\CompanyProjects;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyProjectRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'languages' => ['required', 'array'],
            'languages.*' => ['string', 'exists:languages,id'],
            'prod_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
