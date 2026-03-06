<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum HardSkillLevelsEnum: string
{
    use EnumHelper;

    case FUNDAMENTALS = "fundamentals";
    case BASIC = "basic";
    case INTERMEDIATE = "intermediate";
    case ADVANCED = "advanced";
    case EXPERT = "expert";
    case AUTHORITY = "authority";

    public function label(): string
    {
        return match($this){
            self::FUNDAMENTALS => "Fundamentals",
            self::BASIC => "Basic",
            self::INTERMEDIATE => "Intermediate",
            self::ADVANCED => "Advanced",
            self::EXPERT => "Expert",
            self::AUTHORITY => "Authority",
        };
    }
}
