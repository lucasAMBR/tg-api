<?php

namespace App\Http\Requests\PortfolioSolicitation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioSolicitationRequest extends FormRequest
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
            'id' => [
                'required',
                'uuid',
                'exists:portfolio_solicitations,id'
            ],
            'portfolio_url' => [
                'required',
                'url',
                'max:255'
            ]
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
