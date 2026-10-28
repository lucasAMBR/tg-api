<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum TranslationStatusEnum: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case TRANSLATING = 'translating';
    case TRANSLATED = 'translated';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::TRANSLATING => 'Traduzindo',
            self::TRANSLATED => 'Traduzido',
            self::ERROR => 'Erro',
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => 'translation.status.pending',
            self::TRANSLATING => 'translation.status.translating',
            self::TRANSLATED => 'translation.status.translated',
            self::ERROR => 'translation.status.error',
        };
    }
}
