<?php

namespace App\Http\Requests\Profiles\ClientProfiles;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientProfileRequest extends FormRequest
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
            'cpf' => ['required', new Cpf, 'unique:client_profiles,cpf'],
            'bio' => ['required', 'string'],
            'birthdate' => ['required', 'date', 'date_format:Y-m-d'],
        ];
    }
}
