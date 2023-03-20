<?php

namespace App\Event\SubContractor;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class SubContractorServiceAddedEvent extends Event
{
    public const NAME = 'subcontractor.service.added';

    public function __construct(
        protected User $user,
    ){}

    public function getUser(): User
    {
        return $this->user;
    }
}
