<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum SalaryTypeEnum: string
{

    use EnumHelper;

    case PER_DAY = 'per_day';
    case PER_WEEK = 'per_week';
    case PER_MONTH = 'per_month';

    public function i18nKey(): string
    {
        return match ($this) {
            self::PER_DAY => 'enum.salary_type.per_day',
            self::PER_WEEK => 'enum.salary_type.per_week',
            self::PER_MONTH => 'enum.salary_type.per_month',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PER_DAY => 'Per day',
            self::PER_WEEK => 'Per week',
            self::PER_MONTH => 'Per month',
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::PER_DAY => 'Por dia',
            self::PER_WEEK => 'Por semana',
            self::PER_MONTH => 'Por mês',
        };
    }
}
