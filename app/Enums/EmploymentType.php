<?php

namespace App\Enums;

use App\Traits\EnumHelper;

// Esse é referente as modalidades de trabalho
enum EmploymentType: string
{
    use EnumHelper;

    case ON_SITE= "on_site";
    case HYBRID = "hybrid";
    case REMOTE = "remote";

    public function label(): string
    {
        return match($this){
            self::ON_SITE => "On Site",
            self::HYBRID => "Hybrid",
            self::REMOTE => "Remote",
        };
    }
}
