<?php

namespace App\Http\Requests\ProficiencyTest;

use App\Enums\ProficiencyTestVisualizationTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProficiencyTestVisualizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:proficiency_tests,id'],
            'type' => ['required', Rule::enum(ProficiencyTestVisualizationTypeEnum::class)],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
