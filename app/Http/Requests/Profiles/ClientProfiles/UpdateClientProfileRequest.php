<?php

namespace App\Http\Requests\Profiles\ClientProfiles;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientProfileRequest extends FormRequest
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
            'cpf' => ['sometimes', new Cpf],
            'bio' => ['sometimes', 'string'],
            'birthdate' => ['sometimes', 'date', 'date_format:Y-m-d'],
        ];
    }
}
