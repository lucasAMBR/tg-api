<?php

namespace App\Http\Requests\FreelanceProposal;

use App\Enums\FreelanceProposalPricingTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFreelanceProposalRequest extends FormRequest
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
        return array_merge([
            'id' => [
                'required',
                'uuid',
                'exists:freelance_job_vacancies,id,deleted_at,NULL'
            ],
        ], $this->proposalRules());
    }

    /**
     * O orçamento vem com o preço cheio (`fixed`) ou com o valor da hora e as horas
     * estimadas (`hourly`). Os campos da outra forma de cobrança são ignorados
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function proposalRules(): array
    {
        return [
            'pricing_type' => [
                'required',
                new Enum(FreelanceProposalPricingTypeEnum::class),
            ],
            'total_price' => [
                'exclude_unless:pricing_type,' . FreelanceProposalPricingTypeEnum::FIXED->value,
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'hourly_rate' => [
                'exclude_unless:pricing_type,' . FreelanceProposalPricingTypeEnum::HOURLY->value,
                'required',
                'numeric',
                'gt:0',
                'max:9999.99',
            ],
            'estimated_hours' => [
                'exclude_unless:pricing_type,' . FreelanceProposalPricingTypeEnum::HOURLY->value,
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
            'message' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }

    public function attributes(): array
    {
        return [
            'pricing_type' => 'Forma de cobrança',
            'total_price' => 'Preço total',
            'hourly_rate' => 'Valor da hora',
            'estimated_hours' => 'Horas estimadas',
            'message' => 'Mensagem',
        ];
    }
}
