<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum FreelanceJobVacancyStatusEnum: string
{

    use EnumHelper;

    case OPEN = "open";
    case IN_PROGRESS = "in_progress";
    case COMPLETED = "completed";

    public function i18nKey(): string
    {
        return match ($this) {
            self::OPEN => "enum.freelance_job_vacancy_status.open",
            self::IN_PROGRESS => "enum.freelance_job_vacancy_status.in_progress",
            self::COMPLETED => "enum.freelance_job_vacancy_status.completed",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OPEN => "Open",
            self::IN_PROGRESS => "In progress",
            self::COMPLETED => "Completed",
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::OPEN => 'Aberto',
            self::IN_PROGRESS => 'Em andamento',
            self::COMPLETED => 'Concluído',
        };
    }

    /**
     * Só o freela aberto recebe orçamentos e pode ser editado: depois que o cliente
     * escolhe um orçamento, o escopo fica fechado com o dev contratado
     */
    public function acceptsProposals(): bool
    {
        return $this === self::OPEN;
    }
}
