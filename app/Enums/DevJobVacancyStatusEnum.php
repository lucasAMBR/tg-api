<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DevJobVacancyStatusEnum: string
{

    use EnumHelper;

    case IN_PROGRESS = "in_progress";
    case APPROVED = "approved";
    case REJECTED = "rejected";

    public function i18nKey() {
        return match($this) {
            self::IN_PROGRESS => "enum.dev_job_vacancy_status.in_progress",
            self::APPROVED => "enum.dev_job_vacancy_status.approved",
            self::REJECTED => "enum.dev_job_vacancy_status.rejected"
        };
    }

    public function label(): string {
        return match($this) {
            self::IN_PROGRESS => "In progress",
            self::APPROVED => "Approved",
            self::REJECTED => "Rejected"
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::IN_PROGRESS => 'Em andamento',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Recusado',
        };
    }
}
