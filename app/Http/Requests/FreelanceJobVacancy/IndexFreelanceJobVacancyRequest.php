<?php

namespace App\Http\Requests\FreelanceJobVacancy;

use App\Enums\FreelanceJobTypeEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexFreelanceJobVacancyRequest extends FormRequest
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
            'job_type' => [
                'nullable',
                new Enum(FreelanceJobTypeEnum::class),
            ],
            'seniority_level' => [
                'nullable',
                new Enum(SeniorityLevelEnum::class),
            ],
            'only_mine' => [
                'nullable',
                'boolean',
            ],
        ]);
    }

    public function prepareForValidation(): void
    {
        $this->prepareBooleanQueryParams(['only_mine']);
    }
}
