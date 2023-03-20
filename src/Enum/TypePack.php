<?php

namespace App\Enum;

enum TypePack: int {
    case CREDIT = 0;
    case FORFAIT_MENSUEL = 1;
    case FORFAIT_ANNUEL = 2;
}
