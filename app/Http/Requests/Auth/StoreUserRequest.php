<?php

namespace App\Http\Requests\Auth;

use App\Rules\Cellphone;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name'  => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'email' => [
                'required',
                'string',
                'unique:users,email',
                'max:255'
            ],
            'cpf' => [
                'required',
                'string',
                'unique:users,cpf',
                new Cpf
            ],
            'phone' => [
                'required',
                'string',
                'unique:users,phone',
                new Cellphone
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ]
        ];
    }
}
