<?php

namespace App\Http\Requests\JobVacancy;

use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

        ]);

    }
}
