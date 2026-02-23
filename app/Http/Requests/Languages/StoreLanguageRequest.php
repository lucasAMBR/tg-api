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
            'name' => ['required', 'string', 'max:255', 'min:1'],
            'slug'        => ['required', 'string'],
            'is_official' => ['required', 'boolean'],
            'is_approved' => ['required', 'boolean'],
        ];

    }

    protected function prepareForValidation()
        {
            $user = request()->user();

            $this->merge([
                'slug' => Str::slug($this->input('name'))
            ]);

            if($user->hasRole('admin')){
                $this->merge([
                    'is_official' => true,
                    'is_approved' => true
                ]);
            }else{
                $this->merge([
                    'is_official' => false,
                    'is_approved' => false
                ]);
            }
        }
}
