<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ProficiencyTestResponseStatusEnum: string
{
    use EnumHelper;

    case AWAITING_RESPONSE = 'awaiting_response';
    case ANSWERED = 'answered';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_RESPONSE => 'Aguardando resposta',
            self::ANSWERED => 'Respondido',
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::AWAITING_RESPONSE => 'proficiency_test_response.status.awaiting_response',
            self::ANSWERED => 'proficiency_test_response.status.answered',
        };
    }
}
