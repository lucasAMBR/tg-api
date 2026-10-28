<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum FreelanceProposalStatusEnum: string
{

    use EnumHelper;

    case PENDING = "pending";
    case ACCEPTED = "accepted";
    case REJECTED = "rejected";

    public function i18nKey(): string
    {
        return match ($this) {
            self::PENDING => "enum.freelance_proposal_status.pending",
            self::ACCEPTED => "enum.freelance_proposal_status.accepted",
            self::REJECTED => "enum.freelance_proposal_status.rejected",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => "Pending",
            self::ACCEPTED => "Accepted",
            self::REJECTED => "Rejected",
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando',
            self::ACCEPTED => 'Aceito',
            self::REJECTED => 'Recusado',
        };
    }
}
