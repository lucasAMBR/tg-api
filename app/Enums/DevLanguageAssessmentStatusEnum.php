<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DevLanguageAssessmentStatusEnum: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case ANSWERED = 'answered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando resposta',
            self::ANSWERED => 'Respondida'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => 'enum.dev_language_assessment.status.pending',
            self::ANSWERED => 'enum.dev_language_assessment.status.answered'
        };
    }
}
