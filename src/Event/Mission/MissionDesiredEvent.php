<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionDesiredEvent extends Event
{
    public const NAME = 'mission.desired';

    public function __construct(
        protected User $user,
    ){}

    public function getMission(): User
    {
        return $this->user;
    }
}
