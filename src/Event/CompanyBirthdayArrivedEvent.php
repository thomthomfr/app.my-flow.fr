<?php

namespace App\Event;

use App\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;

class CompanyBirthdayArrivedEvent extends Event
{
    public const NAME = 'company.birthday';

    public function __construct(
        protected Company $company,
    ){}

    public function getCompany(): Company
    {
        return $this->company;
    }

}
