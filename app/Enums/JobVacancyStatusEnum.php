<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum JobVacancyStatusEnum: string
{

    use EnumHelper;

    case PENDING = "pending";
    case APPROVED = "approved";
    case REFUSAL = "refusal";

    public function i18nKey() {
        return match($this) {
            self::PENDING => "enum.job_vacancy_status.pending",
            self::APPROVED => "enum.job_vacancy_status.approved",
            self::REFUSAL => "enum.job_vacancy_status.refusal"
        };
    }

    public function label(): string {
        return match($this) {
            self::PENDING => "Pending",
            self::APPROVED => "Approved",
            self::REFUSAL => "Refusal"
        };
    }
}
