<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum SeniorityLevelEnum: string
{
    use EnumHelper;

    case INTERN = "intern";
    case JUNIOR = "junior";
    case MID_LEVEL = "mid_level";
    case SENIOR = "senior";
    case STAFF = "staff";

    public function label(): string
    {
        return match($this){
            self::INTERN => "Intern",
            self::JUNIOR => "Junior",
            self::MID_LEVEL => "Mid Level",
            self::SENIOR => "Senior",
            self::STAFF => "Staff"
        };
    }

    public static function softSkillsPointLimit(): array
    {
        return [
            SeniorityLevelEnum::INTERN->value => 15,
            SeniorityLevelEnum::JUNIOR->value => 25,
            SeniorityLevelEnum::MID_LEVEL->value => 35,
            SeniorityLevelEnum::SENIOR->value => 45,
            SeniorityLevelEnum::STAFF->value => 50,
        ];
    }
}
