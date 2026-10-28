<?php

namespace App\Http\Requests\Profiles\DevProfiles;

use App\Enums\DevSpecialtyEnum;
use App\Enums\SeniorityLevelEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDevProfileRequest extends FormRequest
{
    use IndexRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareBooleanQueryParams(['open_to_relocation', 'open_to_work']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'seniority_level' => ['nullable', Rule::enum(SeniorityLevelEnum::class)],
            'specialty' => ['nullable', Rule::enum(DevSpecialtyEnum::class)],
            'open_to_relocation' => ['nullable', 'boolean'],
            'open_to_work' => ['nullable', 'boolean'],
        ]);
    }
}
