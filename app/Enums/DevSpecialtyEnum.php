<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DevSpecialtyEnum: string
{
    use EnumHelper;

    case FRONTEND = 'frontend';
    case BACKEND = 'backend';
    case FULLSTACK = 'fullstack';

    public function i18nKey(): string
    {
        return match ($this) {
            self::FRONTEND => 'enum.dev_specialty.frontend',
            self::BACKEND => 'enum.dev_specialty.backend',
            self::FULLSTACK => 'enum.dev_specialty.fullstack'
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FRONTEND => 'Frontend',
            self::BACKEND => 'Backend',
            self::FULLSTACK => 'Fullstack'
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::FRONTEND => 'Frontend',
            self::BACKEND => 'Backend',
            self::FULLSTACK => 'Fullstack'
        };
    }
}
