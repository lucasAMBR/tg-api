<?php

namespace App\Http\Requests\Profiles\DevProfiles;

use App\Enums\SeniorityLevelEnum;
use App\Rules\Cellphone;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDevProfileRequest extends FormRequest
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
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user()?->dev_profile()->exists()) {
                $validator->errors()->add(
                    'dev_profile',
                    'You already have a developer profile.'
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'unique:dev_profiles,cpf', new Cpf],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'bio' => ['required', 'string'],
            'phone' => ['required', new Cellphone],
            'open_to_relocation' => ['nullable', 'boolean'],
            'open_to_work' => ['nullable', 'boolean'],
            'birthdate' => ['required', 'date', 'date_format:Y-m-d'],
            'seniority_level' => ['required', Rule::enum(SeniorityLevelEnum::class)],
        ];
    }
}
