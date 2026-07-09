<?php

namespace App\Http\Requests\ProficiencyTest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitProficiencyTestRequest extends FormRequest
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
            'chunks' => ['required', 'array', 'min:1'],
            'chunks.*.time_taken' => ['required', 'integer', 'min:0'],
            'chunks.*.alt_tabs' => ['required', 'integer', 'min:0'],
            'chunks.*.responses' => ['required', 'array', 'min:1'],
            'chunks.*.responses.*.question_id' => ['required', 'uuid', 'exists:questions,id'],
            'chunks.*.responses.*.response_id' => ['required', 'uuid', 'exists:question_responses,id'],
        ];
    }
}
