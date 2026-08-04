<?php

namespace App\Http\Requests\ProjectHistory;

use Illuminate\Foundation\Http\FormRequest;

class RemoveImageFromProjectRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:project_histories,id'],
            'image_id' => ['required', 'integer', 'exists:media,id']
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
            'image_id' => $this->route('image_id')
        ]);
    }
}
