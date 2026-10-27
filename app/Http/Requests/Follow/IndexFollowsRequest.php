<?php

namespace App\Http\Requests\Follow;

use App\Traits\IndexRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Usado tanto pela lista de seguidores quanto pela de seguidos, que validam o mesmo usuário.
 */
class IndexFollowsRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:users,id'],
        ]);
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}
