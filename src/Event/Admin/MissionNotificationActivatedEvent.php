<?php

namespace App\Event\Admin;

use App\Entity\Mission;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class MissionNotificationActivatedEvent extends Event
{
    public const NAME = 'mission.notification.activated';

    public function __construct(
        protected Mission $mission,
    ){}

    public function getMission(): Mission
    {
        return $this->mission;
    }
}
