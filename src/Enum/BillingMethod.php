<?php

namespace App\Enum;

enum BillingMethod: int {
    case BILL_TIME_PAST = 1;
    case BILL_PRESTATION = 2;
}
