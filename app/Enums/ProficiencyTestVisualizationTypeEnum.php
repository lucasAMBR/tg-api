<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ProficiencyTestVisualizationTypeEnum: string
{
    use EnumHelper;

    case ENTRY = 'entry';
    case EXIT = 'exit';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada',
            self::EXIT => 'Saída',
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::ENTRY => 'enum.proficiency_test.visualization_type.entry',
            self::EXIT => 'enum.proficiency_test.visualization_type.exit',
        };
    }
}
