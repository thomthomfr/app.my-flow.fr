<?php

namespace App\Enum;

enum Operator: int
{
    case EQUAL = 1;
    case GREATER_THAN = 2;
    case GREATER_THAN_OR_EQUAL = 3;
    case LOWER_THAN = 4;
    case LOWER_THAN_OR_EQUAL = 5;

    public function label(): string
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): string
    {
        return match($value) {
            self::EQUAL => 'égale à',
            self::GREATER_THAN => 'supérieur à',
            self::GREATER_THAN_OR_EQUAL => 'supérieur ou égale à',
            self::LOWER_THAN => 'inférieur à',
            self::LOWER_THAN_OR_EQUAL => 'inférieur ou égale à',
        };
    }
}
