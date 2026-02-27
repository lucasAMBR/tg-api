<?php

namespace App\Http\Requests\ProjectHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectHistoryRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'min:2', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10', 'max:1000'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['sometimes', 'exists:languages,id']
        ];
    }
}
