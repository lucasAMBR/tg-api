<?php

namespace App\Http\Requests\EmploymentHistory;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmploymentHistoryRequest extends FormRequest
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
            'company_name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'company_location' => ['sometimes', 'string', 'min:3', 'max:255'],
            'position_name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'employment_type' => ['sometimes', Rule::enum(EmploymentType::class)],
            'contract_type' => ['sometimes', Rule::enum(ContractType::class)],
            'seniority_level' => ['sometimes', Rule::enum(SeniorityLevelEnum::class)],
            'actuation_details' => ['sometimes', 'string', 'max:600'],
            'is_current' => ['sometimes', 'boolean'],
            'start_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date', 'date_format:Y-m-d', 'after:start_date', 'required_if:is_current,false'],
        ];
    }

    protected function passedValidation(): void
    {

        if ($this->has('is_current') && $this->boolean('is_current')) {
            $this->merge([
                'end_date' => null,
            ]);
        }

        if ($this->has('end_date') && !is_null($this->date['end_date'])) {
            $this->merge([
                'is_current' => false,
            ]);
        }
    }
}
