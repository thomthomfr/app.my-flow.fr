<?php

namespace App\Event;

use App\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;

class CompanyUpdatedEvent extends Event
{
    public const NAME = 'company.updated';

    public function __construct(
        protected Company $company,
    ){}

    public function getCompany(): Company
    {
        return $this->company;
    }

}
