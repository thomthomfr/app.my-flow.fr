<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionRefusedEvent extends Event
{
    public const NAME = 'mission.refused';

    public function __construct(
        protected Mission $mission,
        protected User $intervenant,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getIntervenant(): User
    {
        return $this->intervenant;
    }
}
