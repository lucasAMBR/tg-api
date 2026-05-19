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

    public function i18nKey() {
        return match($this){
            self::FUNDAMENTALS => "enum.hard_skill_levels.fundamentals",
            self::BASIC => "enum.hard_skill_levels.basic",
            self::INTERMEDIATE => "enum.hard_skill_levels.intermediate",
            self::ADVANCED => "enum.hard_skill_levels.advanced",
            self::EXPERT => "enum.hard_skill_levels.expert",
            self::AUTHORITY => "enum.hard_skill_levels.authority",
        };
    }

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
