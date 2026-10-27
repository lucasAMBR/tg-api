<?php

namespace App\Http\Requests\FreelanceJobVacancy;

use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Traits\IndexRequestTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexMyFreelanceContractsRequest extends FormRequest
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
            // Um contrato só existe depois que o freela sai do aberto
            'status' => [
                'nullable',
                (new Enum(FreelanceJobVacancyStatusEnum::class))->only([
                    FreelanceJobVacancyStatusEnum::IN_PROGRESS,
                    FreelanceJobVacancyStatusEnum::COMPLETED,
                ]),
            ],
        ]);
    }
}
