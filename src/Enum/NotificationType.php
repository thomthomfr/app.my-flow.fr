<?php

namespace App\Enum;

enum NotificationType: int
{
    case EMAIL = 0;
    case SMS = 1;
    case MOBILE = 2;

    public function label(): int
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): int
    {
        return match($value) {
            self::EMAIL => 0,
            self::SMS => 1,
            self::MOBILE => 2,
        };
    }
}
