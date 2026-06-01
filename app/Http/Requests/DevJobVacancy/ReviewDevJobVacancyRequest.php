<?php

namespace App\Http\Requests\DevJobVacancy;

use App\Enums\JobVacancyStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ReviewDevJobVacancyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'uuid',
                'exists:dev_job_vacancy,id'
            ],
            'status' => [
                'required', 
                new Enum(JobVacancyStatusEnum::class)
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
