<?php

namespace App\Enums;

use App\Traits\EnumHelper;

/**
 * Como o dev cobra no orçamento: o preço cheio do serviço ou o valor da hora
 * junto com as horas estimadas
 */
enum FreelanceProposalPricingTypeEnum: string
{

    use EnumHelper;

    case FIXED = "fixed";
    case HOURLY = "hourly";

    public function i18nKey(): string
    {
        return match ($this) {
            self::FIXED => "enum.freelance_proposal_pricing_type.fixed",
            self::HOURLY => "enum.freelance_proposal_pricing_type.hourly",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FIXED => "Fixed price",
            self::HOURLY => "Hourly rate",
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::FIXED => 'Preço fechado',
            self::HOURLY => 'Valor por hora',
        };
    }
}
