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
}
