<?php

namespace App\Enum;

enum Manager: int {
    case CLIENT = 0;
    case JOB = 1;

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match($value) {
            self::CLIENT=> 'Client',
            self::JOB => 'Métier',
        };
    }
}
