<?php

namespace App\Http\Resources\Enum;

use App\Enums\FreelanceJobTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Caso do enum `FreelanceJobTypeEnum` com a informação extra de exigir ou não stack,
 * para o front saber quando o campo de linguagens é obrigatório no formulário da vaga.
 *
 * @property-read FreelanceJobTypeEnum $resource
 */
class FreelanceJobTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{value: string, label: string, i18nKey: string, requires_stack: bool}
     */
    public function toArray(Request $request): array
    {
        $case = $this->resource;

        return [
            'value' => $case->value,
            'label' => $case->label(),
            'i18nKey' => $case->i18nKey(),
            'requires_stack' => $case->requiresStack(),
        ];
    }
}
