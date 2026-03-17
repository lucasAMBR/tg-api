<?php

namespace App\Http\Requests\Profiles\ClientProfiles;

use App\Rules\Cellphone;
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
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'bio' => ['sometimes', 'string'],
            'phone' => ['sometimes', new Cellphone],
            'birthdate' => ['sometimes', 'date', 'date_format:Y-m-d'],
        ];
    }
}
