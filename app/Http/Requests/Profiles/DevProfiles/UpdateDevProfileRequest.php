<?php

namespace App\Http\Requests\Profiles\DevProfiles;

use App\Enums\SeniorityLevelEnum;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDevProfileRequest extends FormRequest
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
            'cpf' => ['sometimes', 'unique:dev_profiles,cpf', new Cpf],
            'bio' => ['sometimes', 'string'],
            'birthdate' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'seniority_level' => ['sometimes', Rule::enum(SeniorityLevelEnum::class)],
        ];
    }
}
