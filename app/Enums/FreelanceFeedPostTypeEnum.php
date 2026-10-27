<?php

namespace App\Enums;

use App\Traits\EnumHelper;

/**
 * Tipos de postagem do feed do freela: atualizações livres (de qualquer um dos dois),
 * entregas do dev e reviews do cliente sobre uma entrega
 */
enum FreelanceFeedPostTypeEnum: string
{

    use EnumHelper;

    case UPDATE = "update";
    case DELIVERABLE = "deliverable";
    case REVIEW = "review";

    public function i18nKey(): string
    {
        return match ($this) {
            self::UPDATE => "enum.freelance_feed_post_type.update",
            self::DELIVERABLE => "enum.freelance_feed_post_type.deliverable",
            self::REVIEW => "enum.freelance_feed_post_type.review",
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::UPDATE => "Update",
            self::DELIVERABLE => "Deliverable",
            self::REVIEW => "Review",
        };
    }

    public function labelPt(): string
    {
        return match ($this) {
            self::UPDATE => 'Atualização',
            self::DELIVERABLE => 'Entrega',
            self::REVIEW => 'Review',
        };
    }
}
