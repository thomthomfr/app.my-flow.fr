<?php

namespace App\Enum;

enum Operation: int
{
    case ADMIN_ALERT = 0;
    case PREVIOUS_STEP = 1;
    case NEXT_STEP = 2;
    case EMAIL = 3;
    case PROJECT_MANAGER_ALERT = 4;
    case COMMERCIAL_ALERT = 5;

    public function label(): string
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): string
    {
        return match($value) {
            self::ADMIN_ALERT => 'Alerte admin',
            self::PREVIOUS_STEP => 'Retour étape précédente',
            self::NEXT_STEP => 'Passer à l\'étape suivante',
            self::EMAIL => 'Envoyer un email',
            self::PROJECT_MANAGER_ALERT => 'Alerte chef de projet',
            self::COMMERCIAL_ALERT => 'Alerte commerce',
        };
    }
}
