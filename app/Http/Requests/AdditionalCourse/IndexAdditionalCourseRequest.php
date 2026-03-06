<?php

namespace App\Http\Requests\AdditionalCourse;

use App\Traits\IndexRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class IndexAdditionalCourseRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'dev_profile_id' => ['nullable', 'string', 'exists:dev_profiles,id'],
            'verified' => ['nullable', 'boolean']
        ]);
    }
}
