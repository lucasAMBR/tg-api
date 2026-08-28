<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum JobVacancyStatusEnum: string
{

    use EnumHelper;

    case OPEN_INSCRIPTIONS = "open_inscriptions";
    case CLOSED_INSCRIPTIONS = "closed_inscriptions";
    case CANCELED = "canceled";
    case CONCLUDED = "concluded";

    public function i18nKey() {
        return match($this) {
            self::OPEN_INSCRIPTIONS => "enum.job_vacancy_status.open_inscriptions",
            self::CLOSED_INSCRIPTIONS => "enum.job_vacancy_status.closed_inscriptions",
            self::CANCELED => "enum.job_vacancy_status.canceled",
            self::CONCLUDED => "enum.job_vacancy_status.concluded"
        };
    }

    public function label(): string {
        return match($this) {
            self::OPEN_INSCRIPTIONS => "Open inscriptions",
            self::CLOSED_INSCRIPTIONS => "Closed inscriptions",
            self::CANCELED => "Canceled",
            self::CONCLUDED => "Concluded"
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::OPEN_INSCRIPTIONS => 'Inscrições abertas',
            self::CLOSED_INSCRIPTIONS => 'Inscrições encerradas',
            self::CANCELED => 'Cancelada',
            self::CONCLUDED => 'Concluída',
        };
    }

    /**
     * Indica se a vaga ainda recebe novas candidaturas
     */
    public function acceptsApplications(): bool
    {
        return $this === self::OPEN_INSCRIPTIONS;
    }

    /**
     * Status para os quais a vaga pode transitar a partir do status atual
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::OPEN_INSCRIPTIONS => [self::CLOSED_INSCRIPTIONS, self::CANCELED],
            self::CLOSED_INSCRIPTIONS => [self::CONCLUDED, self::CANCELED],
            self::CANCELED, self::CONCLUDED => []
        };
    }
}
