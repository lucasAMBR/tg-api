<?php

namespace App\Http\Requests\EmploymentHistory;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentHistoryRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'min:3', 'max:255'],
            'company_location' => ['required', 'string', 'min:3', 'max:255'],
            'position_name' => ['required', 'string', 'min:2', 'max:255'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'contract_type' => ['required', Rule::enum(ContractType::class)],
            'seniority_level' => ['required', Rule::enum(SeniorityLevelEnum::class)],
            'actuation_details' => ['nullable', 'string', 'max:600'],
            'is_current' => ['required', 'boolean'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date', 'required_if:is_current,false'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->boolean('is_current')) {
            $this->merge([
                'end_date' => null,
            ]);
        }
    }
}
