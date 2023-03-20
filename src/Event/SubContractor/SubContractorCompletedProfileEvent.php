<?php

namespace App\Event\SubContractor;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class SubContractorCompletedProfileEvent extends Event
{
    public const NAME = 'subcontractor.profile.completed';

    public function __construct(
        protected User $user,
    ){}

    public function getUser(): User
    {
        return $this->user;
    }
}
