<?php

namespace App\Traits;

trait EnumHelper
{
    /**
     * Retorna um array formatado para Selects/Dropdowns
     * Ex: [['value' => 'strategic', 'label' => 'Estratégico'], ...]
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : $case->name,
        ], self::cases());
    }

    /**
     * Retorna apenas os valores (útil para validações manuais)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna um array associativo simples [value => label]
     */
    public static function array(): array
    {
        return array_combine(
            self::values(),
            array_map(fn($case) => method_exists($case, 'label') ? $case->label() : $case->name, self::cases())
        );
    }
}
