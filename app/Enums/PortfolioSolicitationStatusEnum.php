<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum PortfolioSolicitationStatusEnum: string
{
    use EnumHelper;

    case PENDING = 'pending';
    case SENT = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::SENT => 'Enviado'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => 'enum.portfolio_solicitation.status.pending',
            self::SENT => 'enum.portfolio_solicitation.status.sent'
        };
    }
}
