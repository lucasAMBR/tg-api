<?php

namespace App\Http\Resources\Question;

use App\Enums\QuestionCategoryEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representa uma stack de categoria de questão selecionável pelo dev.
 *
 * @property-read QuestionCategoryEnum $resource
 */
class QuestionCategoryStackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{value: string, i18n_key: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->resource->value,
            'i18n_key' => $this->resource->i18nKey(),
        ];
    }
}
