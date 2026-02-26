<?php

namespace App\Http\Requests\ProjectHistory;

use Illuminate\Foundation\Http\FormRequest;

class SaveImagesToProjectRequest extends FormRequest
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
            'images' => ['required'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10000'],
        ];
    }
}
