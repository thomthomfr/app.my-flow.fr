<?php

namespace App\Enum;

enum CompanyContract: int
{
    case PACK_CREDIT = 0;
    case END_OF_MONTH_BILLING = 1;
    case MONTHLY_BILLING = 2;
    case CASH = 3;

    public function label(): int
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): int
    {
        return match($value) {
            self::PACK_CREDIT => 0,
            self::END_OF_MONTH_BILLING => 1,
            self::MONTHLY_BILLING => 2,
            self::CASH => 3,
        };
    }
}
