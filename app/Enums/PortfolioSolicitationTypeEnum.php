<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum PortfolioSolicitationTypeEnum: string
{
    use EnumHelper;

    case REPOSITORY = 'repository';
    case PRODUCTION = 'production';

    public function label(): string
    {
        return match ($this) {
            self::REPOSITORY => 'Repositório',
            self::PRODUCTION => 'Projeto em produção'
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::REPOSITORY => 'enum.portfolio_solicitation.type.repository',
            self::PRODUCTION => 'enum.portfolio_solicitation.type.production'
        };
    }
}
