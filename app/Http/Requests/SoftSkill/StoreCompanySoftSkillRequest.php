<?php

namespace App\Http\Requests\SoftSkill;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanySoftSkillRequest extends FormRequest
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
        // dd('para aq');
        return [

            // Passa um array de soft skill com os IDs das soft skills
            'soft_skills' => ['required', 'array'],
            // Dentro do array de soft skill eu passo o ID das soft skills
            'soft_skills.*' => ['string', 'exists:soft_skills,id']

        ];
    }
}
