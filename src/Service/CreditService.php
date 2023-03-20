<?php

namespace App\Service;

use App\Repository\CreditHistoryRepository;

class CreditService
{
    /**
     * @var CreditHistoryRepository
     */
    private $creditHistory;

    /**
     * @param CreditHistoryRepository $creditHistoryRepository
     */
    public function __construct(CreditHistoryRepository $creditHistoryRepository)
    {
        $this->creditHistory = $creditHistoryRepository;
    }

    /**
     * @param $company
     * @return int|mixed|string
     */
    public function CreditAvailable($company)
    {
        $credit = $this->creditHistory->findAvailableCredit($company);
        return $credit;
    }
}
