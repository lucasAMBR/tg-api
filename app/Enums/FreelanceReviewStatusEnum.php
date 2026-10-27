<?php

namespace App\Enums;

use App\Traits\EnumHelper;

/**
 * Veredito do cliente na review de uma entrega
 */
enum FreelanceReviewStatusEnum: string
{

    use EnumHelper;

    case APPROVED = "approved";
    case CHANGES_REQUESTED = "changes_requested";

    public function i18nKey(): string
    {
        return match ($this) {
            self::APPROVED => "enum.freelance_review_status.approved",
            self::CHANGES_REQUESTED => "enum.freelance_review_status.changes_requested",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => "Approved",
            self::CHANGES_REQUESTED => "Changes requested",
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::APPROVED => 'Aprovada',
            self::CHANGES_REQUESTED => 'Ajustes solicitados',
        };
    }
}
