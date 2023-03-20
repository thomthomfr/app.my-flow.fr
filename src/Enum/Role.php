<?php

namespace App\Enum;

enum Role: string {
    case ROLE_CLIENT = 'ROLE_CLIENT';
    case ROLE_CLIENT_ADMIN = 'ROLE_CLIENT_ADMIN';
    case ROLE_SUBCONTRACTOR = 'ROLE_SUBCONTRACTOR';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_VALIDATOR = 'ROLE_VALIDATOR';
    case ROLE_OBSERVER = 'ROLE_OBSERVER';
    case ROLE_ALL = 'ROLE_ALL';
    case ROLE_COMMERCIAL = 'ROLE_COMMERCIAL';
    case ROLE_PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match($value) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_SUBCONTRACTOR => 'Sous-traitant',
            self::ROLE_CLIENT => 'Client',
            self::ROLE_CLIENT_ADMIN => 'Client administrateur',
            self::ROLE_VALIDATOR => 'Validateur',
            self::ROLE_OBSERVER => 'Observateur',
            self::ROLE_ALL => 'Tous les partenaires',
            self::ROLE_COMMERCIAL => 'Commerce',
            self::ROLE_PROJECT_MANAGER => 'Gestion de projet',
        };
    }

    public function getBackground(): string
    {
        return match($this) {
            self::ROLE_ADMIN => 'danger',
            self::ROLE_SUBCONTRACTOR => 'info',
            self::ROLE_CLIENT, self::ROLE_VALIDATOR, self::ROLE_OBSERVER, self::ROLE_ALL => 'primary',
        };
    }
}
