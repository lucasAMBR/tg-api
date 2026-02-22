<?php

namespace App\Http\Requests\Languages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreLanguageRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            
            'name' => ['required', 'string', 'max:255', 'min:3'],
            'slug' => ['required', 'string'],
            'user_id' => ['nullable', 'exists:users, id'],
            'is_oficial' => ['nullable', 'boolean'],
            'is_approved' => ['nullable', 'boolean']

        ];

    }

    /**
     * Crio uma função para preparar o dado para ser armazenado no banco,
     * é executado antes das rules e ja padroniza o dado
     */
    protected function prepareForValidation()
        {
            // Merge serve para alterar os dados antes de irem para as rules
            $this->merge([
                'slug' => Str::slug($this->slug), // Passo o dado para o padrão slug e para minusculo 
                'slug' => strtolower($this->slug),
                'name' => strtolower(trim($this->name)) // trim() remove espaços em branco da string
            ]);
           
        }
}
