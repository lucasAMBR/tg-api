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

    public function i18nKey() {
        return match($this){
            self::HIGH_SCHOOL => "enum.degree_level.high_school",
            self::TECHNICAL => "enum.degree_level.technical",
            self::ASSOCIATE => "enum.degree_level.associate",
            self::BACHELORS => "enum.degree_level.bachelors",
            self::POST_GRADUATE => "enum.degree_level.post_graduate",
            self::MASTERS => "enum.degree_level.masters",
            self::DOCTORATE => "enum.degree_level.doctorate",
            self::POST_DOCTORATE => "enum.degree_level.post_doctorate",
        };
    }

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
