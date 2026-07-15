<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum ProficiencyTestStatusEnum: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case GENERATED = 'generated';
    case AWAITING_SCORE = 'awaiting_score';
    case COMPLETED = 'completed';
    case GENERATION_FAILED = 'generation_failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::GENERATED => 'Gerado',
            self::AWAITING_SCORE => 'Aguardando pontuação',
            self::COMPLETED => 'Concluído',
            self::GENERATION_FAILED => 'Falha na geração do teste'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => 'proficiency_test.status.pending',
            self::GENERATED => 'proficiency_test.status.generated',
            self::AWAITING_SCORE => 'proficiency_test.status.awaiting_score',
            self::COMPLETED => 'proficiency_test.status.completed',
            self::GENERATION_FAILED => 'proficiency_test.status.generation_failed'
        };
    }
}
