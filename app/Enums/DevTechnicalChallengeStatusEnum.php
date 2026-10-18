<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DevTechnicalChallengeStatusEnum: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando entrega',
            self::DELIVERED => 'Entregue'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => 'enum.dev_technical_challenge.status.pending',
            self::DELIVERED => 'enum.dev_technical_challenge.status.delivered'
        };
    }
}
