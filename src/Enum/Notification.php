<?php

namespace App\Enum;

enum Notification: int
{
    case NEW_COMMENT_IN_CHAT = 0;
    case NEW_COMMENT_IN_CHAT_FOR_STEP = 1;
    case MAJ_STATE = 2;
    case CONSUMPTION_BALANCE = 3;
    case NEW_BILL = 4;
    case NEWSLETTER = 5;

    public function label(): int
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): int
    {
        return match($value) {
            self::NEW_COMMENT_IN_CHAT => 0,
            self::NEW_COMMENT_IN_CHAT_FOR_STEP => 1,
            self::MAJ_STATE => 2,
            self::CONSUMPTION_BALANCE => 3,
            self::NEW_BILL => 4,
            self::NEWSLETTER => 5,
        };
    }
}
