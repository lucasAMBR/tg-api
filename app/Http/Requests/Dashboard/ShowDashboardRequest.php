<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Usado tanto pela dashboard da empresa quanto pela do admin, que aceitam o mesmo período.
 */
class ShowDashboardRequest extends FormRequest
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
            // Quantos meses a série mensal cobre, contando o atual
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
