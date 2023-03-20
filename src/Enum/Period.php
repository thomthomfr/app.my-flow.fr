<?php

namespace App\Enum;

enum Period: int
{
    case ONE_HOUR = 1;
    case TWO_HOURS = 2;
    case SIX_HOURS = 6;
    case TWELVE_HOURS = 12;
    case ONE_DAY = 24;
    case TWO_DAYS = 48;
    case SEVEN_DAYS = 168;
    case FOURTEEN_DAYS = 336;

    public function label(): string
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): string
    {
        return match($value) {
            self::ONE_HOUR => '1 heure',
            self::TWO_HOURS => '2 heures',
            self::SIX_HOURS => '6 heures',
            self::TWELVE_HOURS => '12 heures',
            self::ONE_DAY => '1 jour',
            self::TWO_DAYS => '2 jours',
            self::SEVEN_DAYS => '7 jours',
            self::FOURTEEN_DAYS => '14 jours',
        };
    }
}
