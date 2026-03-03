<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum DegreeLevelEnum: string
{
    use EnumHelper;

    case HIGH_SCHOOL= "high_school";
    case TECHNICAL = "technical";
    case ASSOCIATE = "associate";
    case BACHELORS = "bachelors";
    case POST_GRADUATE = "post_graduate";
    case MASTERS = "masters";
    case DOCTORATE = "doctorate";
    case POST_DOCTORATE = "post_doctorate";

    public function label(): string
    {
        return match($this){
            self::HIGH_SCHOOL => "High School",
            self::TECHNICAL => "Technical",
            self::ASSOCIATE => "Associate",
            self::BACHELORS => "Bachelors",
            self::POST_GRADUATE => "Post Graduate",
            self::MASTERS => "Masters",
            self::DOCTORATE => "Doctorate",
            self::POST_DOCTORATE => "Post Doctorate",
        };
    }
}
