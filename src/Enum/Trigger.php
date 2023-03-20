<?php

namespace App\Enum;

enum Trigger: int
{
    case INACTION = 0;
    case REFUSAL = 1;
    case VALIDATION = 2;
    case ENTER_STEP = 3;
    case RETURN_TO_STEP = 4;
    case EXIT_STEP = 5;
    case CONDITIONAL_OR = 6;
    case CONDITIONAL_AND = 7;
    case RELAUNCH_CLIENT = 8;
    case MISSION_ARCHIVED = 9;

    public function label(): string
    {
        return self::getLabel($this);
    }

    static public function getLabel(self $value): string
    {
        return match($value) {
            self::INACTION => 'Inaction',
            self::REFUSAL => 'Demande de modification',
            self::VALIDATION => 'Validation',
            self::ENTER_STEP => 'Entrée dans l\'étape',
            self::RETURN_TO_STEP => 'Retour dans l\'étape',
            self::EXIT_STEP => 'Sortie de l\'étape',
            self::CONDITIONAL_OR => 'L\'une des conditions ci-dessous',
            self::CONDITIONAL_AND => 'Toutes les conditions ci-dessous',
            self::RELAUNCH_CLIENT => 'Relance client',
            self::MISSION_ARCHIVED => 'Clôture de la mission',
        };
    }
}
