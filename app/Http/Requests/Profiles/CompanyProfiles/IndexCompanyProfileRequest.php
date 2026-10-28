<?php

namespace App\Http\Requests\Profiles\CompanyProfiles;

use App\Enums\OperationalSegmentEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCompanyProfileRequest extends FormRequest
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
            'operational_segment' => ['nullable', Rule::enum(OperationalSegmentEnum::class)],
        ]);
    }
}
