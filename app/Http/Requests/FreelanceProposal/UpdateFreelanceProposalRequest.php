<?php

namespace App\Http\Requests\FreelanceProposal;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * O dev reenvia o orçamento inteiro (forma de cobrança e valores), com as mesmas regras
 * do envio. Só a `message` é opcional: quando não vem, continua a que estava
 */
class UpdateFreelanceProposalRequest extends StoreFreelanceProposalRequest
{
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
                'exists:freelance_proposals,id'
            ],
        ], $this->proposalRules());
    }
}
