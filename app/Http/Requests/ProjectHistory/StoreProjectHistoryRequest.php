<?php

namespace App\Http\Requests\ProjectHistory;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectHistoryRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'languages' => ['required', 'array'],
            'languages.*' => ['string', 'exists:languages,id']
        ];
    }
}
