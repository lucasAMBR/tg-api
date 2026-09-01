<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DevJobVacancyInterviewStatusEnum: string
{
    use EnumHelper;

    // Ainda não há nenhuma proposta de horário
    case AWAITING_SCHEDULE = 'awaiting_schedule';
    // Aguardando o dev confirmar/responder o horário (proposta inicial da empresa
    // ou resposta da empresa a uma contraproposta do dev)
    case AWAITING_DEV_CONFIRMATION = 'awaiting_dev_confirmation';
    // Aguardando a empresa confirmar/responder o horário proposto pelo dev
    case AWAITING_COMPANY_CONFIRMATION = 'awaiting_company_confirmation';
    case CANCELLED = 'cancelled';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_SCHEDULE => 'Aguardando horário',
            self::AWAITING_DEV_CONFIRMATION => 'Aguardando confirmação do dev',
            self::AWAITING_COMPANY_CONFIRMATION => 'Aguardando confirmação da empresa',
            self::CANCELLED => 'Cancelada',
            self::APPROVED => 'Aprovada',
            self::REJECTED => 'Rejeitada',
        };
    }

    public function labelPt(): string
    {
        return $this->label();
    }

    public function i18nKey(): string
    {
        return match ($this) {
            self::AWAITING_SCHEDULE => 'enum.dev_job_vacancy_interview.status.awaiting_schedule',
            self::AWAITING_DEV_CONFIRMATION => 'enum.dev_job_vacancy_interview.status.awaiting_dev_confirmation',
            self::AWAITING_COMPANY_CONFIRMATION => 'enum.dev_job_vacancy_interview.status.awaiting_company_confirmation',
            self::CANCELLED => 'enum.dev_job_vacancy_interview.status.cancelled',
            self::APPROVED => 'enum.dev_job_vacancy_interview.status.approved',
            self::REJECTED => 'enum.dev_job_vacancy_interview.status.rejected',
        };
    }
}
