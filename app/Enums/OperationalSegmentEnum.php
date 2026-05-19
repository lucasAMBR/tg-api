<?php

namespace App\Enums;

use App\Traits\EnumHelper;

enum OperationalSegmentEnum: string
{
    use EnumHelper;

    case E_COMMERCE = "e_commerce";
    case FINTECH = "fintech";
    case HEALTHCARE = "healthcare";
    case EDTECH = "edtech";
    case LOGISTICS = "logistics";
    case CYBERSECURITY = "cybersecurity";
    case GAME_DEV = "game_dev";
    case CLOUD_COMPUTING = "cloud_computing";
    case ARTIFICIAL_INTELLIGENCE = "artificial_intelligence";
    case SOFTWARE_HOUSE = "software_house";
    case RETAIL = "retail";
    case BANKING = "banking";

    public function label(): string
    {
        return match($this) {
            self::E_COMMERCE => "E-commerce",
            self::FINTECH => "Fintech",
            self::HEALTHCARE => "Healthcare & HealthTech",
            self::EDTECH => "EdTech (Education)",
            self::LOGISTICS => "Logistics & Supply Chain",
            self::CYBERSECURITY => "Cybersecurity",
            self::GAME_DEV => "Game Development",
            self::CLOUD_COMPUTING => "Cloud Computing / SaaS",
            self::ARTIFICIAL_INTELLIGENCE => "AI / Machine Learning",
            self::SOFTWARE_HOUSE => "Software House / Agency",
            self::RETAIL => "Retail / Varejo",
            self::BANKING => "Banking & Financial Services",
        };
    }

    public function i18nKey() {
        return match($this){
            self::E_COMMERCE => "enum.operational_segment.e_commerce",
            self::FINTECH => "enum.operational_segment.fintech",
            self::HEALTHCARE => "enum.operational_segment.healthcare",
            self::EDTECH => "enum.operational_segment.edtech",
            self::LOGISTICS => "enum.operational_segment.logistics",
            self::CYBERSECURITY => "enum.operational_segment.cybersecurity",
            self::GAME_DEV => "enum.operational_segment.game_dev",
            self::CLOUD_COMPUTING => "enum.operational_segment.cloud_computing",
            self::ARTIFICIAL_INTELLIGENCE => "enum.operational_segment.artificial_intelligence",
            self::SOFTWARE_HOUSE => "enum.operational_segment.software_house",
            self::RETAIL => "enum.operational_segment.retail",
            self::BANKING => "enum.operational_segment.banking",
        };
    }
}
