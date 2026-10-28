<?php

namespace App\Http\Requests\FreelanceFeed;

use App\Enums\FreelanceFeedPostTypeEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexFreelanceFeedRequest extends FormRequest
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
            'id' => [
                'required',
                'uuid',
                'exists:freelance_job_vacancies,id,deleted_at,NULL'
            ],
            'type' => [
                'nullable',
                new Enum(FreelanceFeedPostTypeEnum::class),
            ],
        ]);
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
