<?php

namespace App\Http\Resources\Enum;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representa um caso de enum no formato consumido pelos selects do front.
 *
 * @property-read BackedEnum $resource
 */
class EnumResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Todos os enums expostos pela API implementam `label()` e `i18nKey()`, então
     * os dois campos são sempre preenchidos — os fallbacks abaixo são apenas rede
     * de segurança para um enum novo que esqueça de implementá-los.
     *
     * @return array{value: string|int, label: string, i18nKey: string}
     */
    public function toArray(Request $request): array
    {
        $case = $this->resource;

        return [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
            'i18nKey' => method_exists($case, 'i18nKey') ? $case->i18nKey() : $case->name,
        ];
    }
}
