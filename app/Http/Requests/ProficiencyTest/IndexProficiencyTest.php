<?php

namespace App\Http\Requests\ProficiencyTest;

use App\Enums\DevSpecialtyEnum;
use App\Enums\ProficiencyTestStatusEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProficiencyTest extends FormRequest
{
    use IndexRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'dev_profile_id' => ['nullable', 'uuid', 'exists:dev_profiles,id'],
            'seniority_level' => ['nullable', Rule::enum(SeniorityLevelEnum::class)],
            'specialty' => ['nullable', Rule::enum(DevSpecialtyEnum::class)],
            'status' => ['nullable', Rule::enum(ProficiencyTestStatusEnum::class)],
        ]);
    }
}
