<?php

namespace App\Http\Requests\FreelanceFeed;

use App\Enums\FreelanceFeedPostTypeEnum;
use App\Enums\FreelanceReviewStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFreelanceFeedPostRequest extends FormRequest
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
        $review = FreelanceFeedPostTypeEnum::REVIEW->value;

        return [
            'id' => [
                'required',
                'uuid',
                'exists:freelance_job_vacancies,id,deleted_at,NULL'
            ],
            'type' => [
                'required',
                new Enum(FreelanceFeedPostTypeEnum::class),
            ],
            // Toda postagem diz algo: um texto, um anexo ou os dois
            'content' => [
                'required_without:attachments',
                'nullable',
                'string',
                'max:5000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
            ],
            'attachments.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,txt,md,zip,png,jpg,jpeg,webp',
                'max:10000',
            ],
            // A review aponta a entrega revisada e traz o veredito
            'parent_id' => [
                "exclude_unless:type,{$review}",
                'required',
                'uuid',
                'exists:freelance_feed_posts,id,deleted_at,NULL',
            ],
            'review_status' => [
                "exclude_unless:type,{$review}",
                'required',
                new Enum(FreelanceReviewStatusEnum::class),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }

    public function attributes(): array
    {
        return [
            'type' => 'Tipo da postagem',
            'content' => 'Conteúdo',
            'attachments' => 'Anexos',
            'attachments.*' => 'Anexo',
            'parent_id' => 'Entrega revisada',
            'review_status' => 'Resultado da review',
        ];
    }
}
