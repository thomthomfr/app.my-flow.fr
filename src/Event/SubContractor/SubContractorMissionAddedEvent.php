<?php

namespace App\Event\SubContractor;

use App\Entity\User;
use App\Entity\Mission;
use Symfony\Contracts\EventDispatcher\Event;

class SubContractorMissionAddedEvent extends Event
{
    public const NAME = 'subcontractor.mission.added';

    public function __construct(
        protected User $user,
        protected Mission $mission,
    ){}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }
}
