<?php

namespace App\Event\SubContractor;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class SubContractorReferencedEvent extends Event
{
    public const NAME = 'subcontractor.referenced';

    public function __construct(
        protected User $user,
    ){}

    public function getUser(): User
    {
        return $this->user;
    }
}
