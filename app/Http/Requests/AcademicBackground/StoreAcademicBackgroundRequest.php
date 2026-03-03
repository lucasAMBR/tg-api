<?php

namespace App\Http\Requests\AcademicBackground;

use App\Enums\DegreeLevelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicBackgroundRequest extends FormRequest
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
            'degree' => ['required', 'string', 'min:3', 'max:255'],
            'degree_level' => ['required', Rule::enum(DegreeLevelEnum::class)],
            'institution' => ['required', 'string', 'min:3', 'max:255'],
            'certificate' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10000']
        ];
    }
}
