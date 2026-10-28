<?php

namespace App\Http\Requests\JobVacancy;

use App\Enums\JobVacancyStatusEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexJobVacancyRequest extends FormRequest
{

    use IndexRequestTrait;

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

        return array_merge($this->paginationRules(), [
            'company_profile_id' => ['nullable', 'string', 'exists:company_profiles,id'],
            'seniority_level' => ['nullable', new Enum(SeniorityLevelEnum::class)],
            'status' => ['nullable', new Enum(JobVacancyStatusEnum::class)]
        ]);

    }
}
