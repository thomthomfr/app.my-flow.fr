<?php

namespace App\Event\Mission;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionActivatedEvent extends Event
{
    public const NAME = 'mission.activated';

    public function __construct(
        protected Mission $mission,
        protected User $client,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }

    public function getClient(): User
    {
        return $this->client;
    }
}
