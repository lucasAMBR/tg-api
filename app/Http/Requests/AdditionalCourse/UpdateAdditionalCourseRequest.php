<?php

namespace App\Http\Requests\AdditionalCourse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdditionalCourseRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'provider' => ['sometimes', 'string', 'min:3', 'max:255'],
            'certificate' => ['sometimes', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10000']
        ];
    }
}
