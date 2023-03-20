<?php

namespace App\Enum;

enum FreqNotification: int {
    case ALL_NOTIFICATION = 1;
    case ONE_PER_DAY = 2;
    case ONE_PER_WEEK = 3;
}
