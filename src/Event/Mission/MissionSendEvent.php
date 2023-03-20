<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionSendEvent extends Event
{
    public const NAME = 'mission.send';

    public function __construct(
        protected Mission $mission,
        protected User $user,
        protected $role
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getUser(): User
    {
        return $this->user;
    }
    public function getRole(){
        return $this->role;
    }
}
