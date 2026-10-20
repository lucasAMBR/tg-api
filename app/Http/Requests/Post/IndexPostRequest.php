<?php

namespace App\Http\Requests\Post;

use App\Traits\IndexRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class IndexPostRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'sort' => ['nullable', 'string', 'in:recent,popular'],
        ]);
    }
}
