<?php

namespace App\Http\Requests\Comment;

use App\Traits\IndexRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class IndexCommentRequest extends FormRequest
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
            'post_id' => ['required', 'uuid', 'exists:posts,id,deleted_at,NULL'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'order_by' => ['nullable', 'string', 'in:created_at,updated_at'],
        ]);
    }
}
